<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('age')->nullable();
            $table->string('ward')->nullable();         // 병동
            $table->string('bed_number')->nullable();   // 침대번호
            $table->string('diagnosis')->nullable();    // 주진단
            $table->enum('mobility_level', ['full_bedridden', 'partial', 'assisted'])
                  ->default('full_bedridden');         // 와상 정도
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('caregivers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['nurse', 'therapist', 'admin'])->default('nurse');
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                     // 활동명
            $table->string('name_en');                  // 영문 활동명
            $table->integer('phase');                   // 단계 (1~5)
            $table->enum('category', [
                'micro_achievement',    // 1단계: 미세 성취
                'role_assignment',      // 2단계: 역할 부여
                'creation_expression',  // 3단계: 창작/표현
                'social_contribution',  // 4단계: 사회적 기여
                'measurement_feedback'  // 5단계: 측정/피드백
            ]);
            $table->text('description')->nullable();
            $table->string('icon')->default('✨');
            $table->integer('difficulty')->default(1); // 1(쉬움) ~ 5(어려움)
            $table->boolean('voice_enabled')->default(false); // 음성 응답 가능 여부
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->uuid('activity_id');
            $table->uuid('caregiver_id')->nullable();
            $table->enum('status', ['completed', 'attempted', 'skipped'])->default('completed');
            $table->text('patient_response')->nullable();   // 환자 반응/기록
            $table->text('caregiver_note')->nullable();     // 의료진 메모
            $table->integer('self_rating')->nullable();     // 환자 자기평가 1~5
            $table->date('completed_at');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
            $table->foreign('activity_id')->references('id')->on('activities');
        });

        Schema::create('efficacy_assessments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->integer('score');                       // GSES 단축형 총점
            $table->json('responses')->nullable();          // 문항별 응답
            $table->date('assessed_at');
            $table->uuid('assessed_by')->nullable();        // 평가한 의료진
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->string('context_type')->nullable();    // 'encouragement', 'activity', 'assessment'
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::create('wish_tree_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->text('wish');                          // 소원 내용
            $table->string('color')->default('#FFD700');   // 카드 색상
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });

        Schema::create('diary_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('patient_id');
            $table->text('content');                       // 구술 일기 내용
            $table->string('recorded_by')->nullable();     // 받아쓴 사람
            $table->date('entry_date');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('patients')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diary_entries');
        Schema::dropIfExists('wish_tree_items');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('efficacy_assessments');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('caregivers');
        Schema::dropIfExists('patients');
    }
};
