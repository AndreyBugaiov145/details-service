<template>
    <div class="container">
        <h3 @click="loadCategory">{{ category.title }}</h3>
        <div v-if="categories.length">
            <CategoryComponent
                v-for="category in categories" :key="category.id"
                :category="category"
            />
        </div>
        <div v-if="details.length">
            <DetailComponent
                v-for="detail in details" :key="detail.id"
                :detail="detail"
            />
        </div>
    </div>
</template>


<script>
import axios from 'axios'

export default {
    props: ['category'],
    data() {
        return {
            categories: [],
            details: [],
        }
    },
    methods: {
        async loadCategory() {
            let response = await axios.get(`/api/category/children/${this.category.id}`)
            if (response.status) {
                this.categories = response.data.data
                if(!this.categories.length) {
                    this.loadDetails()
                }
            } else {
                alert('Something went wrong try again later.')
            }
        },
        async loadDetails() {
            let response = await axios.get(`/api/detail/category-details/${this.category.id}`)
            if (response.status) {
                this.details = response.data.data
                console.log(this.details)
            } else {
                alert('Something went wrong try again later.')
            }
        },
    },
    mounted() {
    }
}
</script>
