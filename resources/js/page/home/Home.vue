<template>
    <div class="container">
        <CategoryComponent
            v-for="category in categories" :key="category.id"
            :category="category"
        />
    </div>
</template>

<script>

import axios from 'axios'

export default {
    data() {
        return {
            categories : [],
        }
    },
    methods: {
        async loadMainCategories() {
            let response = await axios.get('/api/category/get-main')
            if (response.status) {
                this.categories = response.data.data
            } else {
                alert('Something went wrong try again later.')
            }
        },
    },
    async mounted() {
        await this.loadMainCategories()
    }
}
</script>
