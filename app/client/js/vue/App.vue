<template>
<v-app>
  <router-view />
</v-app>
</template>

<script>
export default {
  name: "App",
  watch: {
    $route(to) {
      this.$store.dispatch("getPageData", to.fullPath)
    }
  },
}
</script>
