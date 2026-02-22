<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 기본 활동 데이터
        $activities = [
            // ── 1단계: 미세 성취 ──
            ['phase' => 1, 'category' => 'micro_achievement', 'icon' => '🖐️',
             'name' => '손가락 구부리기 10회', 'name_en' => 'Finger bending ×10',
             'description' => '양손 손가락을 천천히 쥐었다 펴기를 10회 반복합니다.', 'difficulty' => 1, 'voice_enabled' => false],

            ['phase' => 1, 'category' => 'micro_achievement', 'icon' => '🌬️',
             'name' => '심호흡 3회 스스로 하기', 'name_en' => '3 deep breaths independently',
             'description' => '코로 천천히 들이쉬고 입으로 내쉬기를 3회 반복합니다.', 'difficulty' => 1, 'voice_enabled' => false],

            ['phase' => 1, 'category' => 'micro_achievement', 'icon' => '📅',
             'name' => '오늘 날짜 말하기', 'name_en' => 'State today\'s date',
             'description' => '오늘이 몇 월 며칠인지 스스로 말해봅니다.', 'difficulty' => 1, 'voice_enabled' => true],

            ['phase' => 1, 'category' => 'micro_achievement', 'icon' => '💧',
             'name' => '물 스스로 마시기', 'name_en' => 'Drink water independently',
             'description' => '빨대나 컵을 이용해 혼자 물을 한 모금 마십니다.', 'difficulty' => 2, 'voice_enabled' => false],

            ['phase' => 1, 'category' => 'micro_achievement', 'icon' => '👀',
             'name' => '창밖 보고 한 가지 말하기', 'name_en' => 'Observe and describe outside',
             'description' => '창밖을 보고 눈에 띄는 것 한 가지를 말해봅니다.', 'difficulty' => 1, 'voice_enabled' => true],

            // ── 2단계: 역할 부여 ──
            ['phase' => 2, 'category' => 'role_assignment', 'icon' => '🌱',
             'name' => '식물 상태 보고하기', 'name_en' => 'Plant health report',
             'description' => '침대 옆 화분의 상태를 관찰하고 의료진에게 구두로 보고합니다.', 'difficulty' => 1, 'voice_enabled' => true],

            ['phase' => 2, 'category' => 'role_assignment', 'icon' => '🌤️',
             'name' => '날씨 알리미 역할', 'name_en' => 'Weather reporter',
             'description' => '오늘 창밖 날씨를 보호자나 의료진에게 알려줍니다.', 'difficulty' => 1, 'voice_enabled' => true],

            ['phase' => 2, 'category' => 'role_assignment', 'icon' => '📖',
             'name' => '오늘의 기억 보관자', 'name_en' => 'Memory keeper of the day',
             'description' => '오늘 있었던 일 한 가지를 기억해서 말해봅니다.', 'difficulty' => 2, 'voice_enabled' => true],

            // ── 3단계: 창작/표현 ──
            ['phase' => 3, 'category' => 'creation_expression', 'icon' => '📝',
             'name' => '구술 일기 쓰기', 'name_en' => 'Oral diary entry',
             'description' => '오늘 하루를 입으로 이야기합니다. 의료진이 받아쓰거나 녹음합니다.', 'difficulty' => 2, 'voice_enabled' => true],

            ['phase' => 3, 'category' => 'creation_expression', 'icon' => '🖐️',
             'name' => '손 도장 그림 만들기', 'name_en' => 'Hand stamp artwork',
             'description' => '잉크 패드로 손바닥을 찍어 나만의 작품을 만듭니다.', 'difficulty' => 2, 'voice_enabled' => false],

            ['phase' => 3, 'category' => 'creation_expression', 'icon' => '🌳',
             'name' => '소원 나무에 소원 달기', 'name_en' => 'Hang a wish on the wish tree',
             'description' => '작은 소원을 카드에 말해서 기록하고 소원 나무에 겁니다.', 'difficulty' => 1, 'voice_enabled' => true],

            ['phase' => 3, 'category' => 'creation_expression', 'icon' => '📸',
             'name' => '회상 앨범 이야기하기', 'name_en' => 'Photo memoir narration',
             'description' => '옛날 사진을 보며 그때의 이야기를 들려줍니다.', 'difficulty' => 2, 'voice_enabled' => true],

            // ── 4단계: 사회적 기여 ──
            ['phase' => 4, 'category' => 'social_contribution', 'icon' => '💌',
             'name' => '격려 편지 구술하기', 'name_en' => 'Dictate an encouraging letter',
             'description' => '가족이나 다른 환자에게 힘이 되는 말을 구술합니다.', 'difficulty' => 3, 'voice_enabled' => true],

            ['phase' => 4, 'category' => 'social_contribution', 'icon' => '🎵',
             'name' => '노래 또는 이야기 나누기', 'name_en' => 'Share a song or story',
             'description' => '알고 있는 노래나 재미있는 이야기를 들려줍니다.', 'difficulty' => 2, 'voice_enabled' => true],

            ['phase' => 4, 'category' => 'social_contribution', 'icon' => '🍲',
             'name' => '나만의 레시피 알려주기', 'name_en' => 'Share a personal recipe',
             'description' => '잘 만들던 음식의 레시피를 알려줍니다.', 'difficulty' => 2, 'voice_enabled' => true],

            // ── 5단계: 측정/피드백 ──
            ['phase' => 5, 'category' => 'measurement_feedback', 'icon' => '⭐',
             'name' => '오늘 나를 칭찬하기', 'name_en' => 'Praise yourself today',
             'description' => '오늘 잘 한 점 하나를 스스로 찾아 말해봅니다.', 'difficulty' => 2, 'voice_enabled' => true],

            ['phase' => 5, 'category' => 'measurement_feedback', 'icon' => '📊',
             'name' => '자기효능감 점수 매기기', 'name_en' => 'Rate your self-efficacy',
             'description' => '오늘 "나는 할 수 있다"는 느낌이 몇 점인지 1~10점으로 말해봅니다.', 'difficulty' => 1, 'voice_enabled' => true],
        ];

        foreach ($activities as $activity) {
            DB::table('activities')->insert([
                'id' => Str::uuid(),
                'name' => $activity['name'],
                'name_en' => $activity['name_en'],
                'phase' => $activity['phase'],
                'category' => $activity['category'],
                'description' => $activity['description'],
                'icon' => $activity['icon'],
                'difficulty' => $activity['difficulty'],
                'voice_enabled' => $activity['voice_enabled'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 데모 의료진 계정
        DB::table('caregivers')->insert([
            'id' => Str::uuid(),
            'name' => '김간호사',
            'email' => 'nurse@demo.com',
            'password' => Hash::make('password'),
            'role' => 'nurse',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 데모 환자
        $patientId = Str::uuid();
        DB::table('patients')->insert([
            'id' => $patientId,
            'name' => '이순자',
            'age' => 78,
            'ward' => '3병동',
            'bed_number' => '302호',
            'diagnosis' => '뇌졸중 후유증',
            'mobility_level' => 'full_bedridden',
            'notes' => '좌측 편마비, 의사소통 가능',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
