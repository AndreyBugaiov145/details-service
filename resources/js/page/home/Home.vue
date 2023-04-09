<template>
    <div class="container categories-block pt-3">
        <div class="cat-bg-block">
            <CategoryComponent
                v-for="category in categories" :key="category.id"
                :category="category"
                :authUser="authUser"
            />
        </div>
    </div>
</template>

<script>

import axios from 'axios'

export default {
    data() {
        return {
            categories: [],
            authUser: false
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

        async loadAuthUser() {
            let response = await axios.get('/api/user/me')
            if (response.status) {
                this.authUser = response.data.data
            } else {
                alert('Something went wrong try again later.')
            }
        },

    },
    async mounted() {
        await this.loadMainCategories()
        await this.loadAuthUser()
    }
}
</script>
