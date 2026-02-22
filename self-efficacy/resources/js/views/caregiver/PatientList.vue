<template>
  <div class="patient-list">
    <div class="list-header">
      <h1>환자 목록 <span class="count">{{ patients.length }}명</span></h1>
      <router-link to="/caregiver/patients/new" class="btn-add">+ 환자 등록</router-link>
    </div>

    <div v-if="loading" class="loading">불러오는 중...</div>

    <div v-else class="patients-grid">
      <router-link
        v-for="p in patients"
        :key="p.id"
        :to="`/caregiver/patients/${p.id}`"
        class="patient-card"
        :class="mobilityClass(p.mobility_level)"
      >
        <div class="patient-top">
          <div class="patient-avatar">{{ p.name[0] }}</div>
          <div class="patient-info">
            <h3>{{ p.name }}</h3>
            <p>{{ p.age }}세 · {{ p.ward }} {{ p.bed_number }}</p>
          </div>
          <div class="efficacy-badge" :class="efficacyClass(p.efficacy_score)">
            {{ p.efficacy_score ?? '-' }}점
          </div>
        </div>

        <p class="diagnosis">{{ p.diagnosis || '진단명 없음' }}</p>

        <div class="today-progress">
          <div class="progress-label">
            <span>오늘 활동</span>
            <span class="progress-count">{{ p.today_count }}개 완료</span>
          </div>
          <div class="mini-bar">
            <div class="mini-fill" :style="{ width: Math.min(p.today_count / 17 * 100, 100) + '%' }"></div>
          </div>
        </div>

        <div class="mobility-tag">{{ mobilityLabel(p.mobility_level) }}</div>
      </router-link>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { usePatientStore } from '../../stores/index.js'

const store    = usePatientStore()
const patients = ref([])
const loading  = ref(true)

function mobilityLabel(l) {
  return { full_bedridden: '완전 와상', partial: '부분 와상', assisted: '보조 이동' }[l] ?? l
}
function mobilityClass(l) {
  return { full_bedridden: 'red', partial: 'orange', assisted: 'green' }[l] ?? ''
}
function efficacyClass(score) {
  if (!score) return ''
  if (score >= 33) return 'high'
  if (score >= 22) return 'mid'
  return 'low'
}

onMounted(async () => {
  await store.fetchPatients()
  patients.value = store.patients
  loading.value = false
})
</script>

<style scoped>
.patient-list { padding: 24px; font-family: 'Noto Sans KR', sans-serif; }
.list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
h1 { font-size: 1.5rem; font-weight: 700; }
.count { font-size: 1rem; color: #888; margin-left: 8px; }
.btn-add { background: #FF6B35; color: white; border-radius: 10px; padding: 10px 18px; text-decoration: none; font-weight: 600; }
.patients-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
.patient-card { background: white; border-radius: 16px; padding: 18px; text-decoration: none; color: #333; box-shadow: 0 2px 12px rgba(0,0,0,0.06); border-left: 5px solid #ddd; transition: transform 0.2s; }
.patient-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
.patient-card.red { border-color: #F44336; }
.patient-card.orange { border-color: #FF9800; }
.patient-card.green { border-color: #4CAF50; }
.patient-top { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.patient-avatar { width: 44px; height: 44px; border-radius: 50%; background: #FF6B35; color: white; font-size: 1.2rem; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.patient-info h3 { font-weight: 700; font-size: 1.1rem; margin: 0 0 2px; }
.patient-info p { color: #888; font-size: 0.85rem; margin: 0; }
.efficacy-badge { margin-left: auto; font-size: 0.8rem; font-weight: 700; padding: 4px 10px; border-radius: 20px; background: #F5F5F5; }
.efficacy-badge.high { background: #E8F5E9; color: #2E7D32; }
.efficacy-badge.mid { background: #FFF3E0; color: #E65100; }
.efficacy-badge.low { background: #FFEBEE; color: #C62828; }
.diagnosis { color: #666; font-size: 0.9rem; margin: 0 0 14px; }
.progress-label { display: flex; justify-content: space-between; font-size: 0.8rem; color: #888; margin-bottom: 6px; }
.progress-count { font-weight: 600; color: #FF6B35; }
.mini-bar { background: #F0E8E0; border-radius: 99px; height: 8px; }
.mini-fill { background: linear-gradient(90deg, #FF8C42, #FF6B35); height: 100%; border-radius: 99px; transition: width 0.5s; }
.mobility-tag { display: inline-block; margin-top: 12px; font-size: 0.75rem; padding: 4px 10px; border-radius: 20px; background: #F5F5F5; color: #666; }
</style>
