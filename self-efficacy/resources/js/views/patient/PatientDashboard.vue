<template>
  <div class="patient-dashboard">

    <!-- 헤더: 환자 이름 + 날짜 -->
    <header class="dashboard-header">
      <div class="header-inner">
        <div class="greeting">
          <span class="greeting-wave">👋</span>
          <div>
            <p class="greeting-sub">안녕하세요</p>
            <h1 class="greeting-name">{{ patient?.patient?.name }}님</h1>
          </div>
        </div>
        <div class="date-badge">
          <span class="date-day">{{ today.day }}</span>
          <span class="date-info">{{ today.month }}월 {{ today.date }}일<br>{{ today.weekday }}</span>
        </div>
      </div>
    </header>

    <!-- 오늘의 달성 현황 -->
    <section class="achievement-bar-section">
      <div class="achievement-label">
        <span>오늘의 성취</span>
        <span class="achievement-count">{{ todayCount }} / {{ totalCount }}</span>
      </div>
      <div class="achievement-bar">
        <div class="achievement-fill" :style="{ width: progressPercent + '%' }"></div>
      </div>
      <div class="achievement-stars">
        <span v-for="i in totalCount" :key="i" :class="['star', i <= todayCount ? 'earned' : '']">⭐</span>
      </div>
    </section>

    <!-- 빠른 활동 카드 (오늘 추천) -->
    <section class="quick-actions">
      <h2 class="section-title">지금 할 수 있어요</h2>
      <div class="activity-cards">
        <button
          v-for="activity in suggestedActivities"
          :key="activity.activity.id"
          class="activity-card"
          :class="{ completed: activity.completed }"
          @click="openActivity(activity)"
        >
          <span class="activity-icon">{{ activity.activity.icon }}</span>
          <span class="activity-name">{{ activity.activity.name }}</span>
          <span class="activity-check">{{ activity.completed ? '✅' : '' }}</span>
        </button>
      </div>
    </section>

    <!-- 메뉴 그리드 -->
    <section class="menu-grid">
      <router-link :to="`/patient/${$route.params.id}/activities`" class="menu-item blue">
        <span class="menu-icon">🎯</span>
        <span class="menu-label">전체 활동</span>
      </router-link>
      <router-link :to="`/patient/${$route.params.id}/chat`" class="menu-item green">
        <span class="menu-icon">💬</span>
        <span class="menu-label">AI 대화</span>
      </router-link>
      <router-link :to="`/patient/${$route.params.id}/wish-tree`" class="menu-item yellow">
        <span class="menu-icon">🌳</span>
        <span class="menu-label">소원 나무</span>
      </router-link>
      <router-link :to="`/patient/${$route.params.id}/diary`" class="menu-item purple">
        <span class="menu-icon">📝</span>
        <span class="menu-label">나의 일기</span>
      </router-link>
      <router-link :to="`/patient/${$route.params.id}/assessment`" class="menu-item orange">
        <span class="menu-icon">📊</span>
        <span class="menu-label">자기효능감 측정</span>
      </router-link>
    </section>

    <!-- 활동 완료 모달 -->
    <Teleport to="body">
      <div v-if="modal.open" class="modal-overlay" @click.self="closeModal">
        <div class="modal">
          <div class="modal-header">
            <span class="modal-icon">{{ modal.activity?.icon }}</span>
            <h3>{{ modal.activity?.name }}</h3>
            <p class="modal-desc">{{ modal.activity?.description }}</p>
          </div>

          <div v-if="!modal.done" class="modal-form">
            <label>오늘 어떠셨나요? (선택)</label>
            <textarea v-model="modal.response" placeholder="한 마디 남겨보세요..." rows="3" />

            <label>스스로 점수를 매겨보세요</label>
            <div class="rating-stars">
              <button v-for="i in 5" :key="i" :class="['rating-btn', modal.rating >= i ? 'active' : '']"
                @click="modal.rating = i">{{ i <= modal.rating ? '⭐' : '☆' }}</button>
            </div>

            <button class="btn-complete" @click="submitActivity" :disabled="modal.loading">
              {{ modal.loading ? '기록 중...' : '✅ 완료했어요!' }}
            </button>
          </div>

          <div v-else class="modal-encouragement">
            <div class="encouragement-bubble">
              <span class="streaming-text">{{ modal.encouragement }}</span>
              <span v-if="modal.streaming" class="cursor-blink">|</span>
            </div>
            <button class="btn-close" @click="closeModal">닫기</button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { usePatientStore, useActivityStore } from '../../stores/index.js'

const route       = ref(useRoute())
const patientStore = usePatientStore()
const activityStore = useActivityStore()

const patient   = ref(null)
const todayData = ref(null)
const modal     = ref({ open: false, activity: null, response: '', rating: 0, loading: false, done: false, encouragement: '', streaming: false })

const today = computed(() => {
  const d = new Date()
  const weekdays = ['일', '월', '화', '수', '목', '금', '토']
  return {
    day: d.getDate(),
    month: d.getMonth() + 1,
    date: d.getDate(),
    weekday: weekdays[d.getDay()] + '요일',
  }
})

const todayCount      = computed(() => todayData.value?.completed_count ?? 0)
const totalCount      = computed(() => todayData.value?.total_count ?? 17)
const progressPercent = computed(() => totalCount.value ? Math.round((todayCount.value / totalCount.value) * 100) : 0)

const suggestedActivities = computed(() => {
  if (!todayData.value) return []
  return todayData.value.activities
    .filter(a => a.activity.phase <= 2)
    .slice(0, 4)
})

function openActivity(item) {
  if (item.completed) return
  modal.value = { open: true, activity: item.activity, response: '', rating: 0, loading: false, done: false, encouragement: '', streaming: false }
}

async function submitActivity() {
  modal.value.loading = true
  try {
    await activityStore.completeActivity(
      route.value.params.id,
      modal.value.activity.id,
      { patient_response: modal.value.response, self_rating: modal.value.rating || null },
      (chunk, full) => {
        modal.value.encouragement = full
        modal.value.streaming = true
        modal.value.done = true
      },
      () => { modal.value.streaming = false }
    )
    await activityStore.fetchToday(route.value.params.id).then(d => todayData.value = d)
  } finally {
    modal.value.loading = false
  }
}

function closeModal() {
  modal.value.open = false
}

onMounted(async () => {
  const id = route.value.params.id
  patient.value  = await patientStore.fetchPatient(id)
  todayData.value = await activityStore.fetchToday(id)
})
</script>

<style scoped>
.patient-dashboard { background: #FFF8F0; min-height: 100vh; padding-bottom: 40px; font-family: 'Noto Sans KR', sans-serif; }

.dashboard-header { background: linear-gradient(135deg, #FF8C42, #FF6B35); padding: 24px 20px; color: white; }
.header-inner { display: flex; justify-content: space-between; align-items: center; }
.greeting { display: flex; align-items: center; gap: 12px; }
.greeting-wave { font-size: 2rem; }
.greeting-sub { font-size: 0.85rem; opacity: 0.85; margin: 0; }
.greeting-name { font-size: 1.8rem; font-weight: 700; margin: 0; }
.date-badge { text-align: right; }
.date-day { font-size: 2.5rem; font-weight: 800; display: block; line-height: 1; }
.date-info { font-size: 0.8rem; opacity: 0.85; }

.achievement-bar-section { margin: 20px 20px 0; background: white; border-radius: 16px; padding: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.achievement-label { display: flex; justify-content: space-between; font-size: 0.9rem; font-weight: 600; margin-bottom: 10px; color: #444; }
.achievement-count { color: #FF6B35; }
.achievement-bar { background: #F0E8E0; border-radius: 99px; height: 14px; overflow: hidden; }
.achievement-fill { background: linear-gradient(90deg, #FF8C42, #FF6B35); height: 100%; border-radius: 99px; transition: width 0.6s ease; }
.achievement-stars { display: flex; gap: 4px; margin-top: 10px; flex-wrap: wrap; }
.star { font-size: 1.2rem; filter: grayscale(1); transition: filter 0.3s; }
.star.earned { filter: grayscale(0); }

.quick-actions { margin: 16px 20px 0; }
.section-title { font-size: 1rem; font-weight: 700; color: #333; margin-bottom: 12px; }
.activity-cards { display: flex; flex-direction: column; gap: 10px; }
.activity-card { display: flex; align-items: center; gap: 14px; background: white; border: none; border-radius: 14px; padding: 14px 16px; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.06); text-align: left; transition: all 0.2s; font-size: 1rem; }
.activity-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
.activity-card.completed { opacity: 0.5; cursor: default; }
.activity-icon { font-size: 1.6rem; }
.activity-name { flex: 1; font-weight: 600; color: #333; }
.activity-check { font-size: 1.4rem; }

.menu-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 16px 20px 0; }
.menu-item { display: flex; flex-direction: column; align-items: center; gap: 8px; padding: 16px 8px; border-radius: 16px; text-decoration: none; color: white; font-weight: 700; font-size: 0.85rem; box-shadow: 0 3px 10px rgba(0,0,0,0.12); transition: transform 0.2s; }
.menu-item:hover { transform: translateY(-3px); }
.menu-item.blue { background: linear-gradient(135deg, #2196F3, #1565C0); }
.menu-item.green { background: linear-gradient(135deg, #4CAF50, #2E7D32); }
.menu-item.yellow { background: linear-gradient(135deg, #FFC107, #F57F17); }
.menu-item.purple { background: linear-gradient(135deg, #9C27B0, #6A1B9A); }
.menu-item.orange { background: linear-gradient(135deg, #FF7043, #BF360C); }
.menu-icon { font-size: 2rem; }

/* 모달 */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: flex-end; z-index: 100; }
.modal { background: white; border-radius: 24px 24px 0 0; padding: 28px 24px; width: 100%; max-height: 80vh; overflow-y: auto; }
.modal-header { text-align: center; margin-bottom: 24px; }
.modal-icon { font-size: 3rem; }
.modal-header h3 { font-size: 1.3rem; font-weight: 700; margin: 8px 0 4px; }
.modal-desc { color: #666; font-size: 0.9rem; }
.modal-form label { display: block; font-weight: 600; margin-bottom: 8px; color: #444; }
.modal-form textarea { width: 100%; border: 2px solid #F0E0D0; border-radius: 12px; padding: 12px; font-size: 1rem; resize: none; margin-bottom: 20px; box-sizing: border-box; }
.rating-stars { display: flex; gap: 12px; margin-bottom: 24px; }
.rating-btn { background: none; border: none; font-size: 2rem; cursor: pointer; transition: transform 0.1s; }
.rating-btn.active { transform: scale(1.2); }
.btn-complete { width: 100%; background: linear-gradient(135deg, #FF8C42, #FF6B35); color: white; border: none; border-radius: 14px; padding: 16px; font-size: 1.1rem; font-weight: 700; cursor: pointer; }
.btn-complete:disabled { opacity: 0.6; }
.encouragement-bubble { background: #FFF8F0; border: 2px solid #FFD4B0; border-radius: 16px; padding: 20px; font-size: 1.05rem; line-height: 1.7; color: #444; margin-bottom: 20px; min-height: 80px; }
.cursor-blink { animation: blink 1s infinite; }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }
.btn-close { width: 100%; background: #F5F5F5; border: none; border-radius: 14px; padding: 14px; font-size: 1rem; font-weight: 600; cursor: pointer; color: #555; }
</style>
