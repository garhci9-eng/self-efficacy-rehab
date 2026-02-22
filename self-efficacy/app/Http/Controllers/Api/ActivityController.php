<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\Patient;
use App\Services\AI\EncouragementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    public function __construct(private EncouragementService $ai) {}

    /**
     * GET /api/v1/activities
     * 전체 활동 목록 (단계별 그룹)
     */
    public function index(): JsonResponse
    {
        $activities = Activity::where('active', true)
            ->orderBy('phase')
            ->orderBy('difficulty')
            ->get()
            ->groupBy('phase');

        return response()->json([
            'phases' => [
                1 => ['name' => '미세 성취', 'name_en' => 'Micro Achievement', 'color' => '#4CAF50'],
                2 => ['name' => '역할 부여', 'name_en' => 'Role Assignment',   'color' => '#2196F3'],
                3 => ['name' => '창작 표현', 'name_en' => 'Creation & Expression', 'color' => '#9C27B0'],
                4 => ['name' => '사회적 기여', 'name_en' => 'Social Contribution', 'color' => '#FF9800'],
                5 => ['name' => '측정 피드백', 'name_en' => 'Measurement & Feedback', 'color' => '#F44336'],
            ],
            'activities' => $activities,
        ]);
    }

    /**
     * POST /api/v1/patients/{patient}/activities/{activity}/complete
     * 활동 완료 기록 + AI 격려 메시지 스트리밍
     */
    public function complete(Request $request, Patient $patient, Activity $activity)
    {
        $request->validate([
            'patient_response' => 'nullable|string|max:500',
            'self_rating'      => 'nullable|integer|min:1|max:5',
        ]);

        // 활동 로그 저장
        $log = ActivityLog::create([
            'patient_id'       => $patient->id,
            'activity_id'      => $activity->id,
            'status'           => 'completed',
            'patient_response' => $request->patient_response,
            'self_rating'      => $request->self_rating,
            'completed_at'     => today(),
        ]);

        // SSE 스트리밍 응답
        return response()->stream(function () use ($patient, $activity) {
            $context = [
                'patient_name'    => $patient->name,
                'patient_age'     => $patient->age,
                'diagnosis'       => $patient->diagnosis,
                'activity_name'   => $activity->name,
                'patient_response'=> request('patient_response'),
                'today_count'     => $patient->todayCompletedCount(),
            ];

            echo "event: start\n";
            echo "data: {\"type\":\"start\"}\n\n";
            ob_flush(); flush();

            $fullText = '';
            foreach ($this->ai->generateEncouragement($context) as $chunk) {
                $fullText .= $chunk;
                echo "data: " . json_encode(['type' => 'text', 'text' => $chunk]) . "\n\n";
                ob_flush(); flush();
            }

            echo "event: done\n";
            echo "data: " . json_encode(['type' => 'done', 'full_text' => $fullText]) . "\n\n";
            ob_flush(); flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * GET /api/v1/patients/{patient}/activities/today
     * 오늘 완료/미완료 활동 현황
     */
    public function today(Patient $patient): JsonResponse
    {
        $todayLogs = ActivityLog::where('patient_id', $patient->id)
            ->where('completed_at', today())
            ->with('activity')
            ->get()
            ->keyBy('activity_id');

        $activities = Activity::where('active', true)->orderBy('phase')->get();

        $result = $activities->map(fn($activity) => [
            'activity'  => $activity,
            'completed' => $todayLogs->has($activity->id),
            'log'       => $todayLogs->get($activity->id),
        ]);

        return response()->json([
            'date'            => today()->format('Y-m-d'),
            'activities'      => $result,
            'completed_count' => $todayLogs->where('status', 'completed')->count(),
            'total_count'     => $activities->count(),
        ]);
    }

    /**
     * GET /api/v1/patients/{patient}/activities/history
     * 활동 이력 (최근 30일)
     */
    public function history(Patient $patient): JsonResponse
    {
        $logs = ActivityLog::where('patient_id', $patient->id)
            ->whereBetween('completed_at', [now()->subDays(29)->toDateString(), today()->toDateString()])
            ->with('activity')
            ->orderByDesc('completed_at')
            ->get();

        $heatmap = $logs->where('status', 'completed')
            ->groupBy('completed_at')
            ->map->count();

        return response()->json([
            'logs'    => $logs,
            'heatmap' => $heatmap,
            'weekly'  => $patient->weeklyActivityData(),
        ]);
    }
}
