<template>
  <q-page class="flex flex-center">
    <!-- ローディング中 -->
    <template v-if="loading">
      <div>読み込み中...</div>
    </template>

    <!-- 出勤前 -->
    <template v-else-if="!attendance">
      <div class="attendance-container q-pa-md text-center">
        <q-badge label="勤務外" color="grey" class="text-h5 q-mb-md" />
        <div class="text-h6">{{ todayDate }}</div>
        <div class="text-h3 q-my-md">{{ currentTime }}</div>
        <q-btn
          size="lg"
          label="出勤"
          color="black"
          unelevated
          class="full-width text-h6"
          @click="handleClockIn"
          :loading="loadingIn"
        />
      </div>
    </template>

    <!-- 勤務中 -->
    <template v-else-if="attendance.status_id === workingStatusId">
      <div class="attendance-container q-pa-md text-center">
        <q-badge label="勤務中" color="green" class="text-h5 q-mb-md" />
        <div class="text-h6">{{ todayDate }}</div>
        <div class="text-h3 q-my-md">{{ currentTime }}</div>
        <div class="row q-gutter-sm">
          <q-btn
            unelevated
            color="black"
            class="col text-h6"
            @click="handleClockOut"
            :loading="loadingOut"
          >
            退勤
          </q-btn>
          <q-btn
            outline
            unelevated
            color="black"
            class="col text-h6"
            @click="handleBreakIn"
            :loading="loadingBreak"
          >
            休憩入
          </q-btn>
        </div>
      </div>
    </template>
  </q-page>
</template>

<script setup lang="ts">
defineOptions({ name: 'AttendancePage' });
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Notify } from 'quasar';
import axios from 'axios';
//import { api } from 'boot/axios';
//import { useRouter } from 'vue-router';

const loading = ref(true);
const loadingIn = ref(false);
const loadingOut = ref(false);
const loadingBreak = ref(false);
//const router = useRouter();
const workingStatusId = 2; // 勤務中ステータス
const attendance = ref<null | { status_id: number }>(null);
const now = ref(new Date());

// 今日の日付フォーマット
const todayDate = computed(() => {
  const d = now.value;
  return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日(${['日', '月', '火', '水', '木', '金', '土'][d.getDay()]})`;
});

// 現在の時刻 HH:mm
const currentTime = computed(() => {
  const t = now.value;
  const pad = (n: number) => n.toString().padStart(2, '0');
  return `${pad(t.getHours())}:${pad(t.getMinutes())}`;
});

// 秒単位で now を更新するタイマー
let timerId: ReturnType<typeof setInterval>;
onMounted(async () => {
  timerId = setInterval(() => {
    now.value = new Date();
  }, 1000);

  loading.value = true;
  try {
    await axios.get('/sanctum/csrf-cookie').catch(() => {});
    await fetchTodayAttendance();
  } finally {
    loading.value = false;
  }
});

onUnmounted(() => {
  clearInterval(timerId);
});

/**
 * 本日の勤怠データを取得
 */
async function fetchTodayAttendance() {
  try {
    const { data } = await axios.get('/api/attendance/today');
    attendance.value = data;
  } catch (err: unknown) {
    // 認証エラーならログイン画面へ
    if (axios.isAxiosError(err) && err.response?.status === 401) {
      Notify.create({
        type: 'warning',
        message: 'セッションが切れています。再度ログインしてください。',
      });
      // await router.push('/login');
    }
    // それ以外は「未出勤」状態として扱う
    attendance.value = null;
  }
}

// ボタンクリックで出勤APIを呼ぶ
const handleClockIn = async () => {
  loadingIn.value = true;
  try {
    const { data } = await axios.post('/api/attendance/clock-in');
    attendance.value = data.attendance;
    Notify.create({ type: 'positive', message: '出勤を記録しました。' });
    // 必要に応じてリロードや画面遷移
  } catch (err: unknown) {
    // バリデーションエラーなど
    if (axios.isAxiosError(err) && err.response?.data?.errors) {
      // 型アサーションで errors の形を指定
      const errs = err.response.data.errors as Record<string, string[]>;
      const messages = Object.values(errs).flat(); // => string[]
      messages.forEach((msg) => {
        Notify.create({ type: 'negative', message: msg });
      });
    } else {
      Notify.create({
        type: 'negative',
        message: '出勤に失敗しました。',
      });
    }
  } finally {
    loadingIn.value = false;
  }
};

// 退勤API
const handleClockOut = async () => {
  loadingOut.value = true;
  try {
    await axios.post('/api/attendance/clock-out');
    Notify.create({ type: 'positive', message: '退勤を記録しました。' });
    await fetchTodayAttendance();
  } catch {
    Notify.create({ type: 'negative', message: '退勤に失敗しました。' });
  } finally {
    loadingOut.value = false;
  }
};

// 休憩入API
const handleBreakIn = async () => {
  loadingBreak.value = true;
  try {
    await axios.post('/api/attendance/break-in');
    Notify.create({ type: 'positive', message: '休憩を開始しました。' });
    await fetchTodayAttendance();
  } catch {
    Notify.create({ type: 'negative', message: '休憩開始に失敗しました。' });
  } finally {
    loadingBreak.value = false;
  }
};
</script>

<style scoped>
.attendance-container {
  width: 100%;
  max-width: 360px;
}
.full-width {
  width: 100%;
}
</style>
