import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const usePatientStore = defineStore('patient', () => {
    const patients  = ref([])
    const current   = ref(null)
    const loading   = ref(false)

    async function fetchPatients() {
        loading.value = true
        try {
            const { data } = await axios.get('/api/v1/patients')
            patients.value = data.patients
        } finally {
            loading.value = false
        }
    }

    async function fetchPatient(id) {
        const { data } = await axios.get(`/api/v1/patients/${id}`)
        current.value = data
        return data
    }

    async function createPatient(form) {
        const { data } = await axios.post('/api/v1/patients', form)
        patients.value.push(data.patient)
        return data.patient
    }

    return { patients, current, loading, fetchPatients, fetchPatient, createPatient }
})

export const useActivityStore = defineStore('activity', () => {
    const activities   = ref({})
    const phases       = ref({})
    const todayStatus  = ref(null)
    const history      = ref([])

    async function fetchActivities() {
        const { data } = await axios.get('/api/v1/activities')
        activities.value = data.activities
        phases.value     = data.phases
    }

    async function fetchToday(patientId) {
        const { data } = await axios.get(`/api/v1/patients/${patientId}/activities/today`)
        todayStatus.value = data
        return data
    }

    async function completeActivity(patientId, activityId, payload, onChunk, onDone) {
        const response = await fetch(`/api/v1/patients/${patientId}/activities/${activityId}/complete`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(payload),
        })

        const reader = response.body.getReader()
        const decoder = new TextDecoder()
        let fullText = ''

        while (true) {
            const { done, value } = await reader.read()
            if (done) break

            const text = decoder.decode(value)
            for (const line of text.split('\n')) {
                if (line.startsWith('data: ')) {
                    try {
                        const json = JSON.parse(line.slice(6))
                        if (json.type === 'text' && json.text) {
                            fullText += json.text
                            onChunk?.(json.text, fullText)
                        }
                        if (json.type === 'done') onDone?.(json.full_text)
                    } catch {}
                }
            }
        }
    }

    return { activities, phases, todayStatus, history, fetchActivities, fetchToday, completeActivity }
})

export const useEfficacyStore = defineStore('efficacy', () => {
    const history   = ref([])
    const questions = ref([])

    async function fetchQuestions() {
        const { data } = await axios.get('/api/v1/efficacy/questions')
        questions.value = data.questions
    }

    async function fetchHistory(patientId) {
        const { data } = await axios.get(`/api/v1/patients/${patientId}/efficacy/history`)
        history.value = data.assessments
    }

    async function submit(patientId, responses) {
        const { data } = await axios.post(`/api/v1/patients/${patientId}/efficacy`, { responses })
        history.value.push({ date: '오늘', score: data.score, level: data.level })
        return data
    }

    return { history, questions, fetchQuestions, fetchHistory, submit }
})
