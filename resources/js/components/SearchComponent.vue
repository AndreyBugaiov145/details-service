<template>
    <div>
        <div class="input-group d-flex justify-content-center align-items-center mb-2">
            <span class="mr-3 search-text">Part Number</span>
            <div class="form-outline d-flex justify-content-center align-items-center">
                <input v-model="search" type="search" id="search" placeholder="Пошук" class="form-control uneditable-input-bg"/>
                <button type="button" class="btn btn-dark search-btn" @click="searchDetails">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="red" class="bi bi-search" viewBox="0 0 14 14">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                </button>
            </div>


        </div>
        <small v-if="searchFailLength" class="text-danger">Мінімальна кількість символів 4</small>

        <div class="d-flex flex-column" v-if="details.length && is_searched">
            <div><h5 class="btn btn-secondary" @click="clearSearch">Назад до списку деталей</h5></div>
            <table class="table table-striped ">
                <thead>
                <tr>
                    <th scope="col">Назва</th>
                    <th scope="col"></th>
                    <th scope="col">OEM / Interchange Numbers</th>
                    <th scope="col">Опис</th>
                    <th scope="col">ціна</th>
                    <th v-if="authUser" scope="col">Залишок</th>
                    <th v-if="authUser" scope="col">Редагувати</th>
                </tr>
                </thead>
                <tbody>
                <DetailComponent
                    v-for="detail in details" :key="detail.id"
                    :detail="detail"
                    :authUser="authUser"
                    :updateDetails="updateDetails"
                />

                </tbody>
            </table>
        </div>
        <div class="d-flex flex-column" v-else-if="!details.length && is_searched">
            <div><h5 class="btn btn-secondary" @click="clearSearch">Назад до списку деталей</h5></div>
            <h3 class="d-flex">
                Деталі не знайдено
            </h3>
        </div>


    </div>
</template>


<script>
import axios from 'axios'

export default {
    props: ['authUser', 'startSearch', 'endSearch'],
    data() {
        return {
            is_searched: false,
            search: '',
            searchFailLength: false,
            details: []
        }
    },
    methods: {
        async searchDetails() {
            if (this.search.length < 4) {
                this.searchFailLength = true
                return
            }
            this.searchFailLength = false
            let response = await axios.get(`/api/detail/search?search=${this.search}`)
            if (response.status) {
                this.details = response.data.data
                this.is_searched = true
                this.startSearch()
            } else {
                alert('Something went wrong try again later.')
            }
        },
        async updateDetails() {
            let response = await axios.get(`/api/detail/category-details/${this.category.id}`)
            if (response.status) {
                this.details = response.data.data
                this.showChildCategory = true
                this.showChildCategoryDetails = true
            } else {
                alert('Something went wrong try again later.')
            }
        },
        clearSearch() {
            this.details = []
            this.is_searched = false
            this.search = ''
            this.endSearch()
        }
    },
    mounted() {

    }
}
</script>
