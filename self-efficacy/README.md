# 와상환자 자기효능감 재활 프로그램
# Self-Efficacy Rehabilitation Program for Bedridden Patients

> **아이디어 기여 / Idea Contribution**
> 🧠 프로젝트 아이디어: **사용자 70%** | Project Idea: **User 70%**
> 🤖 구현 보완 아이디어: **Claude (Anthropic) 30%** | Implementation Support: **Claude (Anthropic) 30%**

> **라이선스 / License**
> 본 프로젝트는 **공익적 목적**으로만 사용 가능합니다. 사적 영리 목적의 사용을 금지합니다.
> This project is available for **public benefit purposes only**. Private commercial use is prohibited.

---

## 개요 / Overview

**KO:** 요양병원의 와상환자는 신체적 제약으로 인해 자기효능감이 급격히 저하됩니다. 본 프로젝트는 와상 상태에서도 실질적으로 실행 가능한 5단계 재활 프로그램을 제공합니다. Claude AI와 함께 환자 개인에게 맞춤화된 격려 메시지와 대화를 제공합니다.

**EN:** Bedridden patients in care hospitals experience a rapid decline in self-efficacy. This project provides a 5-phase rehabilitation program that is practically executable even in a bedridden state, with Claude AI providing personalized encouragement and conversation.

## 기술 스택 / Tech Stack

| 구분 | 기술 |
|------|------|
| Backend | Laravel 11, PHP 8.2 |
| Frontend | Vue 3, Pinia, Vue Router |
| AI | Claude API (Anthropic) |
| Database | SQLite (개발) / PostgreSQL (운영) |
| Build | Vite |
| Deploy | Docker |

## 주요 기능 / Features

**환자 화면:**
- 오늘의 활동 달성 현황 (성취 달력)
- 5단계 재활 활동 목록 + 완료 시 AI 격려 메시지 스트리밍
- AI 동반자 채팅 (Claude 기반)
- 소원 나무 (소원 달기/이루어짐 처리)
- 구술 일기 기록
- 자기효능감 측정 (GSES 단축형 10문항) + 변화 그래프

**의료진 화면:**
- 전체 환자 목록 (활동 참여율, 자기효능감 점수)
- 환자별 주간 활동 현황
- 환자 등록/관리

## 빠른 시작 / Quick Start

### 방법 1: Docker (권장)

```bash
git clone https://github.com/your-repo/self-efficacy-rehab.git
cd self-efficacy-rehab

cp .env.example .env.docker
# .env.docker 파일 열어서 ANTHROPIC_API_KEY 입력

docker compose up -d
```

브라우저에서 http://localhost:8080 접속

### 방법 2: 로컬 설치

**요구 사항:** PHP 8.2+, Composer, Node.js 18+

```bash
# 클론
git clone https://github.com/your-repo/self-efficacy-rehab.git
cd self-efficacy-rehab

# 환경 설정
cp .env.example .env
# .env 파일에서 ANTHROPIC_API_KEY 입력

# 의존성 설치
composer install
npm install

# 데이터베이스 설정
touch database/self_efficacy.sqlite
php artisan key:generate
php artisan migrate
php artisan db:seed

# 프론트엔드 빌드
npm run build

# 서버 실행
php artisan serve
```

브라우저에서 http://localhost:8000 접속

## URL 구조

| URL | 화면 |
|-----|------|
| `/caregiver/patients` | 의료진 - 환자 목록 |
| `/caregiver/patients/:id` | 의료진 - 환자 상세 |
| `/patient/:id` | 환자 대시보드 |
| `/patient/:id/activities` | 전체 활동 목록 |
| `/patient/:id/chat` | AI 대화 |
| `/patient/:id/wish-tree` | 소원 나무 |
| `/patient/:id/diary` | 구술 일기 |
| `/patient/:id/assessment` | 자기효능감 측정 |

## API 엔드포인트

```
GET  /api/v1/patients                              환자 목록
POST /api/v1/patients                              환자 등록
GET  /api/v1/patients/:id                          환자 상세
GET  /api/v1/activities                            전체 활동
GET  /api/v1/patients/:id/activities/today         오늘 활동 현황
POST /api/v1/patients/:id/activities/:actId/complete  활동 완료 (SSE)
GET  /api/v1/patients/:id/efficacy/history         자기효능감 이력
POST /api/v1/patients/:id/efficacy                 자기효능감 평가
POST /api/v1/patients/:id/chat                     AI 채팅 (SSE)
GET  /api/v1/patients/:id/wishes                   소원 목록
POST /api/v1/patients/:id/wishes                   소원 추가
GET  /api/v1/patients/:id/diary                    일기 목록
POST /api/v1/patients/:id/diary                    일기 추가
```

## 5단계 재활 프로그램

| 단계 | 이름 | 예시 활동 |
|------|------|----------|
| 1 | 미세 성취 | 손가락 구부리기, 심호흡, 날짜 말하기 |
| 2 | 역할 부여 | 식물 관찰자, 날씨 알리미, 기억 보관자 |
| 3 | 창작 표현 | 구술 일기, 손 도장 그림, 소원 나무 |
| 4 | 사회적 기여 | 격려 편지, 노래/이야기 나눔, 레시피 공유 |
| 5 | 측정 피드백 | 자기효능감 점수, 자기칭찬, 달성 달력 |

## 공익 라이선스 고지

본 프로젝트의 모든 콘텐츠는 공익적 비영리 목적으로만 사용 가능합니다.
요양병원, 복지관, 비영리 의료기관의 실무 적용은 허용됩니다.
상업적 판매, 유료 서비스화, 사적 영리 이용은 금지됩니다.

*"와상의 몸에도, 살아있는 의지가 있다."*
*"Even in a bedridden body, there is a living will."*
