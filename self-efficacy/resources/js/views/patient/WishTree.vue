<template>
  <div class="wish-tree-view">
    <div class="tree-header">
      <h2>🌳 소원 나무</h2>
      <p>소원을 말하면 나무에 달아드릴게요</p>
    </div>

    <!-- 소원 입력 -->
    <div class="wish-input-area">
      <textarea
        v-model="newWish"
        placeholder="소원을 말씀해주세요..."
        rows="3"
        class="wish-textarea"
        maxlength="100"
      />
      <div class="wish-colors">
        <button v-for="color in colors" :key="color"
          :class="['color-dot', selectedColor === color ? 'selected' : '']"
          :style="{ background: color }"
          @click="selectedColor = color"
        />
      </div>
      <button class="btn-wish" @click="addWish" :disabled="!newWish.trim() || loading">
        🌳 나무에 달기
      </button>
    </div>

    <!-- 소원 나무 시각화 -->
    <div class="tree-visual">
      <div class="tree-trunk">
        <div class="tree-crown">
          <div v-if="wishes.length === 0" class="empty-tree">아직 소원이 없어요<br>첫 번째 소원을 달아보세요!</div>
          <div
            v-for="(wish, i) in wishes"
            :key="wish.id"
            class="wish-card"
            :style="{ background: wish.color, transform: `rotate(${wishRotation(i)}deg) translate(${wishX(i)}px, ${wishY(i)}px)` }"
          >
            <span class="wish-text">{{ wish.wish }}</span>
            <button class="wish-done" @click="removeWish(wish)" title="소원이 이루어졌어요!">🌟</button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route         = useRoute()
const wishes        = ref([])
const newWish       = ref('')
const selectedColor = ref('#FFD700')
const loading       = ref(false)
const colors        = ['#FFD700', '#FFB347', '#87CEEB', '#98FB98', '#DDA0DD', '#F08080', '#B0E0E6']

function wishRotation(i) { return (i % 2 === 0 ? 1 : -1) * (5 + (i * 7) % 15) }
function wishX(i)        { return (i % 3 - 1) * 30 + (i * 13) % 20 - 10 }
function wishY(i)        { return -Math.floor(i / 3) * 20 + (i * 7) % 15 }

async function fetchWishes() {
  const { data } = await axios.get(`/api/v1/patients/${route.params.id}/wishes`)
  wishes.value = data.wishes
}

async function addWish() {
  if (!newWish.value.trim()) return
  loading.value = true
  try {
    await axios.post(`/api/v1/patients/${route.params.id}/wishes`, {
      wish: newWish.value.trim(),
      color: selectedColor.value,
    })
    newWish.value = ''
    await fetchWishes()
  } finally {
    loading.value = false
  }
}

async function removeWish(wish) {
  if (confirm(`"${wish.wish}" 소원이 이루어졌나요? 🌟`)) {
    await axios.delete(`/api/v1/patients/${route.params.id}/wishes/${wish.id}`)
    await fetchWishes()
  }
}

onMounted(fetchWishes)
</script>

<style scoped>
.wish-tree-view { padding: 20px; font-family: 'Noto Sans KR', sans-serif; min-height: 100vh; background: #F0F7F0; }
.tree-header { text-align: center; margin-bottom: 20px; }
.tree-header h2 { font-size: 1.4rem; font-weight: 700; }
.tree-header p { color: #888; }

.wish-input-area { background: white; border-radius: 16px; padding: 16px; margin-bottom: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
.wish-textarea { width: 100%; border: 2px solid #E8F5E9; border-radius: 12px; padding: 12px; font-size: 1rem; resize: none; margin-bottom: 12px; box-sizing: border-box; }
.wish-colors { display: flex; gap: 10px; margin-bottom: 14px; }
.color-dot { width: 28px; height: 28px; border-radius: 50%; border: 3px solid transparent; cursor: pointer; transition: transform 0.2s; }
.color-dot.selected { border-color: #333; transform: scale(1.2); }
.btn-wish { width: 100%; background: linear-gradient(135deg, #4CAF50, #2E7D32); color: white; border: none; border-radius: 12px; padding: 14px; font-size: 1rem; font-weight: 700; cursor: pointer; }
.btn-wish:disabled { opacity: 0.5; }

.tree-visual { position: relative; min-height: 400px; display: flex; justify-content: center; }
.tree-trunk { position: relative; display: flex; flex-direction: column; align-items: center; }
.tree-crown { position: relative; width: 280px; min-height: 300px; background: radial-gradient(ellipse at 50% 60%, #4CAF50, #2E7D32); border-radius: 50% 50% 40% 40%; display: flex; flex-wrap: wrap; align-items: center; justify-content: center; padding: 20px; gap: 8px; }
.empty-tree { color: rgba(255,255,255,0.7); text-align: center; font-size: 0.9rem; line-height: 1.6; }
.wish-card { background: #FFD700; border-radius: 10px; padding: 8px 10px; font-size: 0.8rem; max-width: 90px; text-align: center; box-shadow: 2px 4px 8px rgba(0,0,0,0.2); position: relative; display: flex; flex-direction: column; gap: 4px; }
.wish-text { color: #333; font-weight: 600; word-break: keep-all; line-height: 1.4; }
.wish-done { background: none; border: none; cursor: pointer; font-size: 0.9rem; padding: 0; }
</style>
