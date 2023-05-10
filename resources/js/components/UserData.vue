<template>
  <div class="mb-4 pb-4">
    <h2>Returned user data</h2>
    <pre class="code">{{prettyData}}</pre>
  </div>
</template>

<script>
export default {
  props: ["userData"],
  computed: {
    prettyData() {
      if (typeof this.userData === "string") {
        return JSON.stringify(JSON.parse(this.userData), null, 4);
      } else {
        return JSON.stringify(this.userData, null, 4);
      }
    },
  },
};
</script>
