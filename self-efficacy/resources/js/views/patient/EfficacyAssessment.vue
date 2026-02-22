<template>
  <div class="assessment-view">
    <div class="assessment-header">
      <h2>자기효능감 측정</h2>
      <p>솔직하게 답해주세요. 맞고 틀린 답이 없어요 😊</p>
    </div>

    <!-- 이전 점수 그래프 -->
    <div v-if="history.length > 1" class="score-chart">
      <h3>나의 변화</h3>
      <div class="chart-bars">
        <div v-for="(item, i) in history.slice(-7)" :key="i" class="chart-col">
          <div class="bar-wrap">
            <div class="bar-fill" :style="{ height: (item.score / 40 * 100) + '%' }" :class="item.level"></div>
          </div>
          <span class="bar-label">{{ item.date }}</span>
          <span class="bar-score">{{ item.score }}</span>
        </div>
      </div>
    </div>

    <!-- 질문지 -->
    <form v-if="!result" @submit.prevent="submit" class="questions-form">
      <div v-for="(q, i) in questions" :key="q.id" class="question-item">
        <p class="question-text"><span class="q-num">{{ i + 1 }}</span> {{ q.text }}</p>
        <div class="answer-options">
          <button
            v-for="opt in options"
            :key="opt.value"
            type="button"
            :class="['option-btn', responses[i] === opt.value ? 'selected' : '']"
            @click="responses[i] = opt.value"
          >{{ opt.label }}</button>
        </div>
      </div>

      <button type="submit" class="btn-submit" :disabled="!allAnswered || loading">
        {{ loading ? '분석 중...' : '📊 결과 보기' }}
      </button>
    </form>

    <!-- 결과 -->
    <div v-else class="result-view">
      <div class="result-circle" :class="result.level">
        <span class="result-score">{{ result.score }}</span>
        <span class="result-max">/ 40</span>
      </div>
      <h3 class="result-level">{{ result.level === '높음' ? '🌟 높은 자기효능감이에요!' : result.level === '보통' ? '💪 좋아지고 있어요!' : '🌱 함께 성장해봐요!' }}</h3>
      <p class="result-msg">{{ resultMessage }}</p>
      <button class="btn-retry" @click="reset">다시 측정하기</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useEfficacyStore } from '../../stores/index.js'

const route   = useRoute()
const store   = useEfficacyStore()
const loading = ref(false)
const result  = ref(null)
const responses = ref(Array(10).fill(null))
const history = ref([])
const questions = ref([])

const options = [
  { value: 1, label: '전혀 그렇지 않다' },
  { value: 2, label: '그렇지 않다' },
  { value: 3, label: '그렇다' },
  { value: 4, label: '매우 그렇다' },
]

const allAnswered = computed(() => responses.value.every(r => r !== null))
const resultMessage = computed(() => {
  if (!result.value) return ''
  const s = result.value.score
  if (s >= 33) return '스스로 해낼 수 있다는 믿음이 강하게 있어요. 오늘도 잘 하고 계세요!'
  if (s >= 22) return '조금씩 자신감이 쌓이고 있어요. 매일 작은 것부터 해나가면 충분해요.'
  return '지금 힘드실 수 있어요. 괜찮아요, 작은 것 하나부터 시작해봐요. 함께할게요.'
})

async function submit() {
  loading.value = true
  try {
    result.value = await store.submit(route.params.id, responses.value)
    history.value = store.history
  } finally {
    loading.value = false
  }
}

function reset() {
  result.value = null
  responses.value = Array(10).fill(null)
}

onMounted(async () => {
  await store.fetchQuestions()
  await store.fetchHistory(route.params.id)
  questions.value = store.questions
  history.value   = store.history
})
</script>

<style scoped>
.assessment-view { padding: 20px; font-family: 'Noto Sans KR', sans-serif; }
.assessment-header { text-align: center; margin-bottom: 24px; }
.assessment-header h2 { font-size: 1.4rem; font-weight: 700; margin-bottom: 8px; }
.assessment-header p { color: #888; }

.score-chart { background: white; border-radius: 16px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.score-chart h3 { font-size: 0.95rem; font-weight: 700; margin-bottom: 16px; }
.chart-bars { display: flex; align-items: flex-end; gap: 8px; height: 100px; }
.chart-col { display: flex; flex-direction: column; align-items: center; flex: 1; gap: 4px; }
.bar-wrap { flex: 1; width: 100%; display: flex; align-items: flex-end; background: #F5F5F5; border-radius: 6px; overflow: hidden; }
.bar-fill { width: 100%; border-radius: 6px; transition: height 0.6s; min-height: 4px; }
.bar-fill.높음 { background: #4CAF50; }
.bar-fill.보통 { background: #FF9800; }
.bar-fill.낮음 { background: #F44336; }
.bar-label { font-size: 0.7rem; color: #999; }
.bar-score { font-size: 0.75rem; font-weight: 700; color: #555; }

.question-item { background: white; border-radius: 14px; padding: 16px; margin-bottom: 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.question-text { font-size: 1rem; font-weight: 600; margin-bottom: 12px; display: flex; align-items: flex-start; gap: 10px; }
.q-num { background: #FF6B35; color: white; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.8rem; font-weight: 700; flex-shrink: 0; }
.answer-options { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
.option-btn { border: 2px solid #E8E8E8; background: white; border-radius: 10px; padding: 10px 6px; font-size: 0.85rem; cursor: pointer; transition: all 0.2s; text-align: center; }
.option-btn.selected { border-color: #FF6B35; background: #FFF3EE; color: #FF6B35; font-weight: 700; }
.btn-submit { width: 100%; background: linear-gradient(135deg, #FF8C42, #FF6B35); color: white; border: none; border-radius: 14px; padding: 16px; font-size: 1.1rem; font-weight: 700; cursor: pointer; margin-top: 8px; }
.btn-submit:disabled { opacity: 0.5; }

.result-view { text-align: center; padding: 40px 20px; }
.result-circle { width: 140px; height: 140px; border-radius: 50%; display: inline-flex; flex-direction: column; align-items: center; justify-content: center; margin-bottom: 24px; }
.result-circle.높음 { background: radial-gradient(circle, #E8F5E9, #4CAF50); }
.result-circle.보통 { background: radial-gradient(circle, #FFF3E0, #FF9800); }
.result-circle.낮음 { background: radial-gradient(circle, #FFEBEE, #F44336); }
.result-score { font-size: 3rem; font-weight: 800; color: white; line-height: 1; }
.result-max { font-size: 1rem; color: rgba(255,255,255,0.8); }
.result-level { font-size: 1.3rem; font-weight: 700; margin-bottom: 12px; }
.result-msg { color: #666; line-height: 1.7; margin-bottom: 28px; }
.btn-retry { background: #F5F5F5; border: none; border-radius: 12px; padding: 14px 28px; font-size: 1rem; cursor: pointer; font-weight: 600; }
</style>
