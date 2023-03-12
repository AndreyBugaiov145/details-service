<template>
    <div class="container">
        <h1 @click="">{{ analogyDetail.brand }} - {{ analogyDetail.years }}</h1>
    </div>
</template>


<script>
import axios from 'axios'

export default {
    props: ['analogyDetail'],
    data() {
        return {
            categories: [],
        }
    },
    methods: {},
    mounted() {
    }
}
</script>
