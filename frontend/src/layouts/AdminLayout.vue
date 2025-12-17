<template>
  <div class="layout">
    <aside class="sidebar">
      <h3>Admin</h3>
      <ul>
        <li><router-link to="/">Dashboard</router-link></li>
        <li><router-link to="/users">Kullanıcılar</router-link></li>
        <li><router-link to="/roles">Roller</router-link></li>
        <li><router-link to="/permissions">Yetkiler</router-link></li>
        <li><router-link to="/tables">Tablolar</router-link></li>
        <li><router-link to="/columns">Kolonlar</router-link></li>
      </ul>
    </aside>

    <main class="content">
      <router-view />
    </main>
  </div>
</template>

<style scoped>
.layout {
  display: flex;
  height: 100vh;
}
.sidebar {
  width: 220px;
  background: #1f2937;
  color: white;
  padding: 16px;
}
.sidebar a {
  color: white;
  text-decoration: none;
}
.sidebar li {
  margin: 10px 0;
}
.content {
  flex: 1;
  padding: 20px;
}
</style>
