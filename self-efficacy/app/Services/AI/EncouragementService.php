<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class EncouragementService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key');
        $this->model  = config('services.anthropic.model', 'claude-opus-4-6');
    }

    /**
     * 활동 완료 후 격려 메시지 생성 (스트리밍)
     */
    public function generateEncouragement(array $context): \Generator
    {
        $systemPrompt = $this->loadPrompt('encouragement');

        $userMessage = sprintf(
            "환자 정보:\n- 이름: %s\n- 나이: %s세\n- 진단: %s\n\n완료한 활동: %s\n환자 반응: %s\n오늘 완료한 활동 수: %d개",
            $context['patient_name'],
            $context['patient_age'] ?? '알 수 없음',
            $context['diagnosis'] ?? '기재 없음',
            $context['activity_name'],
            $context['patient_response'] ?? '특이 반응 없음',
            $context['today_count'] ?? 1
        );

        yield from $this->streamChat($systemPrompt, $userMessage);
    }

    /**
     * 오늘의 활동 제안
     */
    public function suggestActivity(array $patientContext): string
    {
        $systemPrompt = $this->loadPrompt('activity_suggestion');

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 500,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => json_encode($patientContext, JSON_UNESCAPED_UNICODE)]
            ],
        ]);

        return $response->json('content.0.text', '오늘도 할 수 있는 것부터 시작해봐요!');
    }

    /**
     * 환자와 AI 대화 (스트리밍)
     */
    public function chat(string $patientName, array $messages): \Generator
    {
        $systemPrompt = sprintf(
            $this->loadPrompt('patient_chat'),
            $patientName
        );

        $lastMessage = end($messages);
        yield from $this->streamChat($systemPrompt, $lastMessage['content'], 
            array_slice($messages, 0, -1));
    }

    /**
     * 주간 자기효능감 분석 요약
     */
    public function analyzeWeeklyProgress(array $data): string
    {
        $prompt = sprintf(
            "다음은 요양병원 와상환자 '%s'의 지난 7일간 재활 활동 데이터입니다.\n\n%s\n\n의료진을 위한 간략한 분석 요약을 한국어로 작성해주세요. 200자 이내로.",
            $data['patient_name'],
            json_encode($data['weekly_data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 300,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        return $response->json('content.0.text', '데이터 분석 중 오류가 발생했습니다.');
    }

    private function streamChat(string $system, string $userContent, array $history = []): \Generator
    {
        $messages = array_merge(
            array_map(fn($m) => ['role' => $m['role'], 'content' => $m['content']], $history),
            [['role' => 'user', 'content' => $userContent]]
        );

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://api.anthropic.com/v1/messages',
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_POSTFIELDS     => json_encode([
                'model'      => $this->model,
                'max_tokens' => 800,
                'stream'     => true,
                'system'     => $system,
                'messages'   => $messages,
            ]),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION  => function ($curl, $data) use (&$buffer) {
                $buffer .= $data;
                return strlen($data);
            },
        ]);

        $buffer = '';
        $lines  = [];

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($curl, $data) use (&$lines) {
            foreach (explode("\n", $data) as $line) {
                $line = trim($line);
                if (str_starts_with($line, 'data: ')) {
                    $json = substr($line, 6);
                    if ($json === '[DONE]') continue;
                    $decoded = json_decode($json, true);
                    if (isset($decoded['delta']['text'])) {
                        $lines[] = $decoded['delta']['text'];
                    }
                }
            }
            return strlen($data);
        });

        curl_exec($ch);
        curl_close($ch);

        foreach ($lines as $text) {
            yield $text;
        }
    }

    private function loadPrompt(string $name): string
    {
        $path = base_path("prompts/{$name}.txt");
        return file_exists($path) ? file_get_contents($path) : $this->defaultPrompt($name);
    }

    private function defaultPrompt(string $name): string
    {
        return match($name) {
            'encouragement' => <<<PROMPT
당신은 요양병원에서 와상환자의 재활을 돕는 따뜻한 AI 동반자입니다.
환자가 활동을 완료했을 때 진심 어린 격려 메시지를 작성해주세요.

규칙:
- 150자 이내로 작성
- 존댓말 사용
- 구체적인 활동 내용을 언급하여 개인화
- 과도한 칭찬보다 진심 어린 응원
- 다음 활동에 대한 작은 기대감으로 마무리
- 이모지 1~2개 사용 가능
PROMPT,
            'activity_suggestion' => <<<PROMPT
당신은 요양병원 와상환자의 재활을 돕는 AI 치료사입니다.
환자 상태에 맞는 오늘의 활동을 부드럽게 제안해주세요.
100자 이내로, 존댓말로, 환자 이름을 불러주세요.
PROMPT,
            'patient_chat' => <<<PROMPT
당신은 요양병원 와상환자 '%s'님의 AI 동반자입니다.
환자가 외롭거나 힘들 때 곁에 있어주는 따뜻한 대화 상대입니다.

규칙:
- 항상 존댓말
- 의료적 조언은 하지 않음 (의료진 상담 권유)
- 환자의 감정을 먼저 공감
- 200자 이내로 답변
- 과거 이야기, 소원, 가족 이야기를 자연스럽게 이끌어내기
PROMPT,
            default => '당신은 요양병원 와상환자를 돕는 AI입니다.',
        };
    }
}
