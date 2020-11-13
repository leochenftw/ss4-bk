<template>
<v-main>
    <component :is="site_data.pagetype" />
</v-main>
</template>

<script>
import * as components from "./index.js"
export default {
    name: "Base",
    components: { ...components },
    computed: {
        site_data() {
            return { pagetype: "HomePage" }
        }
    }
}
</script>
