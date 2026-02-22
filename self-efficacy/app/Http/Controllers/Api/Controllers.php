<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Models\EfficacyAssessment;
use App\Models\ChatMessage;
use App\Models\WishTreeItem;
use App\Models\DiaryEntry;
use App\Services\AI\EncouragementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

// ── 환자 컨트롤러 ──────────────────────────────────────────
class PatientController extends Controller
{
    public function index(): JsonResponse
    {
        $patients = Patient::orderBy('ward')->orderBy('bed_number')->get()->map(fn($p) => [
            'id'             => $p->id,
            'name'           => $p->name,
            'age'            => $p->age,
            'ward'           => $p->ward,
            'bed_number'     => $p->bed_number,
            'diagnosis'      => $p->diagnosis,
            'mobility_level' => $p->mobility_level,
            'today_count'    => $p->todayCompletedCount(),
            'efficacy_score' => $p->latestEfficacyScore(),
        ]);

        return response()->json(['patients' => $patients]);
    }

    public function show(Patient $patient): JsonResponse
    {
        return response()->json([
            'patient'        => $patient,
            'today_count'    => $patient->todayCompletedCount(),
            'efficacy_score' => $patient->latestEfficacyScore(),
            'weekly'         => $patient->weeklyActivityData(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:50',
            'age'            => 'nullable|integer|min:1|max:150',
            'ward'           => 'nullable|string|max:50',
            'bed_number'     => 'nullable|string|max:20',
            'diagnosis'      => 'nullable|string|max:200',
            'mobility_level' => 'required|in:full_bedridden,partial,assisted',
            'notes'          => 'nullable|string|max:500',
        ]);

        $patient = Patient::create($validated);
        return response()->json(['patient' => $patient], 201);
    }
}

// ── 자기효능감 평가 컨트롤러 ───────────────────────────────
class EfficacyController extends Controller
{
    // GSES 단축형 문항 (10문항)
    private array $questions = [
        ['id' => 1, 'text' => '무언가 하려고 마음먹으면 대부분 해낼 수 있다'],
        ['id' => 2, 'text' => '어려운 일이 있어도 포기하지 않는다'],
        ['id' => 3, 'text' => '내가 원하는 것을 얻기 위해 노력한다'],
        ['id' => 4, 'text' => '새로운 것도 배울 수 있다'],
        ['id' => 5, 'text' => '힘든 상황도 여러 방법으로 해결할 수 있다'],
        ['id' => 6, 'text' => '노력하면 목표를 이룰 수 있다'],
        ['id' => 7, 'text' => '어떤 일이 생겨도 잘 대처할 수 있다'],
        ['id' => 8, 'text' => '나는 내 생활을 스스로 관리할 수 있다'],
        ['id' => 9, 'text' => '나는 필요한 것을 표현할 수 있다'],
        ['id' => 10, 'text' => '오늘도 해낼 수 있다는 자신감이 있다'],
    ];

    public function questions(): JsonResponse
    {
        return response()->json(['questions' => $this->questions]);
    }

    public function store(Request $request, Patient $patient): JsonResponse
    {
        $request->validate([
            'responses' => 'required|array|min:10',
            'responses.*' => 'integer|min:1|max:4',
        ]);

        $score = array_sum($request->responses);

        $assessment = EfficacyAssessment::create([
            'patient_id'  => $patient->id,
            'score'       => $score,
            'responses'   => $request->responses,
            'assessed_at' => today(),
        ]);

        return response()->json([
            'assessment' => $assessment,
            'score'      => $score,
            'max_score'  => 40,
            'level'      => $this->scoreLevel($score),
        ], 201);
    }

    public function history(Patient $patient): JsonResponse
    {
        $assessments = $patient->efficacyAssessments()
            ->orderBy('assessed_at')
            ->get()
            ->map(fn($a) => [
                'date'  => $a->assessed_at->format('m/d'),
                'score' => $a->score,
                'level' => $this->scoreLevel($a->score),
            ]);

        return response()->json(['assessments' => $assessments]);
    }

    private function scoreLevel(int $score): string
    {
        return match(true) {
            $score >= 33 => '높음',
            $score >= 22 => '보통',
            default      => '낮음',
        };
    }
}

// ── AI 채팅 컨트롤러 ───────────────────────────────────────
class ChatController extends Controller
{
    public function __construct(private EncouragementService $ai) {}

    public function chat(Request $request, Patient $patient)
    {
        $request->validate(['message' => 'required|string|max:500']);

        // 메시지 저장
        ChatMessage::create([
            'patient_id' => $patient->id,
            'role'       => 'user',
            'content'    => $request->message,
        ]);

        $history = $patient->chatMessages()
            ->orderBy('created_at')
            ->take(20)
            ->get(['role', 'content'])
            ->toArray();

        return response()->stream(function () use ($patient, $history) {
            echo "event: start\ndata: {}\n\n";
            ob_flush(); flush();

            $fullText = '';
            foreach ($this->ai->chat($patient->name, $history) as $chunk) {
                $fullText .= $chunk;
                echo "data: " . json_encode(['text' => $chunk]) . "\n\n";
                ob_flush(); flush();
            }

            // 응답 저장
            ChatMessage::create([
                'patient_id' => $patient->id,
                'role'       => 'assistant',
                'content'    => $fullText,
            ]);

            echo "event: done\ndata: " . json_encode(['full_text' => $fullText]) . "\n\n";
            ob_flush(); flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function history(Patient $patient): JsonResponse
    {
        $messages = $patient->chatMessages()->orderBy('created_at')->take(50)->get();
        return response()->json(['messages' => $messages]);
    }
}

// ── 소원 나무 컨트롤러 ─────────────────────────────────────
class WishTreeController extends Controller
{
    public function index(Patient $patient): JsonResponse
    {
        return response()->json(['wishes' => $patient->wishTreeItems()->orderByDesc('created_at')->get()]);
    }

    public function store(Request $request, Patient $patient): JsonResponse
    {
        $request->validate([
            'wish'  => 'required|string|max:100',
            'color' => 'nullable|string|max:10',
        ]);

        $item = WishTreeItem::create([
            'patient_id' => $patient->id,
            'wish'       => $request->wish,
            'color'      => $request->color ?? '#FFD700',
        ]);

        return response()->json(['wish' => $item], 201);
    }

    public function destroy(Patient $patient, WishTreeItem $wish): JsonResponse
    {
        $wish->delete();
        return response()->json(['message' => '소원이 이루어졌어요! 🌟']);
    }
}

// ── 일기 컨트롤러 ─────────────────────────────────────────
class DiaryController extends Controller
{
    public function index(Patient $patient): JsonResponse
    {
        $entries = $patient->diaryEntries()->orderByDesc('entry_date')->take(30)->get();
        return response()->json(['entries' => $entries]);
    }

    public function store(Request $request, Patient $patient): JsonResponse
    {
        $request->validate([
            'content'     => 'required|string|max:1000',
            'recorded_by' => 'nullable|string|max:50',
        ]);

        $entry = DiaryEntry::create([
            'patient_id'  => $patient->id,
            'content'     => $request->content,
            'recorded_by' => $request->recorded_by,
            'entry_date'  => today(),
        ]);

        return response()->json(['entry' => $entry], 201);
    }
}
