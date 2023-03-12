<template>
    <div class="container">
        <h3 @click="loadAnalogyDetail">{{ detail.title }}</h3>

        <div v-if="analogyDetails.length">
            <AnalogyDetailComponent
                v-for="analogyDetail in analogyDetails" :key="analogyDetail.id"
                :analogyDetail="analogyDetail"
            />
        </div>
    </div>
</template>


<script>
import axios from 'axios'

export default {
    props: ['detail'],
    data() {
        return {
            analogyDetails: [],
        }
    },
    methods: {
        async loadAnalogyDetail() {
            let response = await axios.get(`/api/detail/${this.detail.id}/analogy-details`)
            if (response.status) {
                this.analogyDetails = response.data.data
            } else {
                alert('Something went wrong try again later.')
            }
        },
    },
    mounted() {
    }
}
</script>
