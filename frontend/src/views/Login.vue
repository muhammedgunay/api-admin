<script setup>
import { ref } from 'vue'
import { login } from '../api/auth'
import { useRouter } from 'vue-router'

const email = ref('')
const password = ref('')
const error = ref('')
const router = useRouter()

const submit = async () => {
  error.value = ''
  try {
    const res = await login({
      email: email.value,
      password: password.value,
    })

    localStorage.setItem('token', res.data.token)
    router.push('/')
  } catch (e) {
    error.value = 'Login başarısız'
  }
}
</script>

<template>
  <h1>Login</h1>

  <input v-model="email" placeholder="Email" />
  <input v-model="password" type="password" placeholder="Şifre" />

  <button @click="submit">Giriş Yap</button>

  <p v-if="error" style="color:red">{{ error }}</p>
</template>
