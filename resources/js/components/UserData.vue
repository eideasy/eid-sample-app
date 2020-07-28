<template>
  <div>
    <h2 class="text-center">Returned user data</h2>
    <pre>
{{prettyData}}
        </pre>
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
