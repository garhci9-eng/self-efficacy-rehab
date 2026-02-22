<template>
  <div class="chat-view">
    <div class="chat-header">
      <div class="ai-avatar">🤖</div>
      <div>
        <h2>AI 동반자</h2>
        <p>언제든 이야기를 나눠요</p>
      </div>
    </div>

    <div class="messages-area" ref="messagesEl">
      <div v-if="messages.length === 0" class="chat-welcome">
        <p>👋 안녕하세요!<br>무슨 이야기든 편하게 해주세요.<br>오늘 하루는 어떠셨나요?</p>
      </div>

      <div
        v-for="(msg, i) in messages"
        :key="i"
        :class="['message', msg.role]"
      >
        <div v-if="msg.role === 'assistant'" class="msg-avatar">🤖</div>
        <div class="msg-bubble">
          <span>{{ msg.content }}</span>
          <span v-if="msg.streaming" class="cursor-blink">|</span>
        </div>
      </div>
    </div>

    <div class="chat-input-area">
      <div class="quick-replies">
        <button v-for="r in quickReplies" :key="r" class="quick-btn" @click="sendMessage(r)">{{ r }}</button>
      </div>
      <div class="input-row">
        <textarea
          v-model="inputText"
          placeholder="하고 싶은 말을 적어주세요..."
          rows="2"
          class="chat-input"
          @keydown.enter.prevent="sendMessage(inputText)"
        />
        <button class="btn-send" @click="sendMessage(inputText)" :disabled="!inputText.trim() || loading">
          {{ loading ? '⏳' : '전송' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import axios from 'axios'

const route      = useRoute()
const messages   = ref([])
const inputText  = ref('')
const loading    = ref(false)
const messagesEl = ref(null)

const quickReplies = ['오늘 많이 힘들었어요', '가족 이야기 하고 싶어요', '잘 했다고 칭찬해주세요', '심심해요']

async function loadHistory() {
  const { data } = await axios.get(`/api/v1/patients/${route.params.id}/chat/history`)
  messages.value = data.messages
  scrollBottom()
}

async function sendMessage(text) {
  if (!text.trim() || loading.value) return
  const msg = text.trim()
  inputText.value = ''
  loading.value   = true

  messages.value.push({ role: 'user', content: msg })

  const aiMsg = ref({ role: 'assistant', content: '', streaming: true })
  messages.value.push(aiMsg.value)
  scrollBottom()

  try {
    const response = await fetch(`/api/v1/patients/${route.params.id}/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      body: JSON.stringify({ message: msg }),
    })

    const reader  = response.body.getReader()
    const decoder = new TextDecoder()

    while (true) {
      const { done, value } = await reader.read()
      if (done) break
      const text = decoder.decode(value)
      for (const line of text.split('\n')) {
        if (line.startsWith('data: ')) {
          try {
            const json = JSON.parse(line.slice(6))
            if (json.text) {
              aiMsg.value.content += json.text
              await nextTick(); scrollBottom()
            }
            if (json.full_text) aiMsg.value.streaming = false
          } catch {}
        }
      }
    }
  } finally {
    loading.value = false
    aiMsg.value.streaming = false
  }
}

function scrollBottom() {
  nextTick(() => {
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
  })
}

onMounted(loadHistory)
</script>

<style scoped>
.chat-view { display: flex; flex-direction: column; height: 100vh; font-family: 'Noto Sans KR', sans-serif; background: #F5F5F5; }
.chat-header { background: linear-gradient(135deg, #4CAF50, #2E7D32); color: white; padding: 16px 20px; display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.ai-avatar { font-size: 2rem; }
.chat-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0 0 2px; }
.chat-header p { font-size: 0.8rem; opacity: 0.85; margin: 0; }

.messages-area { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; }
.chat-welcome { background: white; border-radius: 16px; padding: 20px; text-align: center; color: #666; line-height: 1.7; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }

.message { display: flex; align-items: flex-end; gap: 8px; }
.message.user { flex-direction: row-reverse; }
.msg-avatar { font-size: 1.5rem; flex-shrink: 0; }
.msg-bubble { max-width: 75%; padding: 12px 16px; border-radius: 18px; font-size: 0.95rem; line-height: 1.6; word-break: keep-all; }
.message.user .msg-bubble { background: #4CAF50; color: white; border-bottom-right-radius: 4px; }
.message.assistant .msg-bubble { background: white; color: #333; border-bottom-left-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.cursor-blink { animation: blink 1s infinite; }
@keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0; } }

.chat-input-area { background: white; padding: 12px 16px; flex-shrink: 0; border-top: 1px solid #eee; }
.quick-replies { display: flex; gap: 8px; overflow-x: auto; margin-bottom: 10px; padding-bottom: 4px; }
.quick-btn { background: #E8F5E9; border: none; border-radius: 20px; padding: 8px 14px; font-size: 0.85rem; cursor: pointer; white-space: nowrap; color: #2E7D32; font-weight: 600; }
.input-row { display: flex; gap: 10px; align-items: flex-end; }
.chat-input { flex: 1; border: 2px solid #E8E8E8; border-radius: 12px; padding: 10px 14px; font-size: 0.95rem; resize: none; }
.btn-send { background: #4CAF50; color: white; border: none; border-radius: 12px; padding: 10px 18px; font-weight: 700; cursor: pointer; white-space: nowrap; }
.btn-send:disabled { opacity: 0.5; }
</style>
