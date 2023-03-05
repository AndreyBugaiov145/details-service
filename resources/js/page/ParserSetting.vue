<template>
    <div class="container">
        <br>
        <h3>Якщо моделі машин не задано будуть взяті всі моделі цього бренду</h3>
        <button type="button" class="btn btn-success mb-5" @click="showModal">Add new</button>
        <div>
            <b-modal id="editSetting"
                     title="Edit"
                     @hidden="setEditableNull"
                     @ok="updateSetting"
            >
                <form>
                    <div class="form-group">
                        <label for="brand">Бренд</label>
                        <input type="text" v-model.trim="setting.brand" class="form-control" id="brand" aria-describedby="emailHelp">
                    </div>
                    <div class="form-group">
                        <label for="car_models">Моделі машин (Вводити через кому!)</label>
                        <input type="text" v-model.trim="setting.car_models" class="form-control" id="car_models" aria-describedby="emailHelp">
                    </div>
                    <div class="form-group">
                        <label for="year">Рік</label>
                        <input type="number" v-model="setting.year" class="form-control" id="year">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" v-model="setting.is_show" type="checkbox" id="is_show" checked>
                        <label class="form-check-label" for="is_show">Показувати клієнтам</label>
                    </div>
                </form>
            </b-modal>
        </div>

        <table class="table">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Бренд</th>
                <th scope="col">Моделі машин</th>
                <th scope="col">Рік</th>
                <th scope="col">Показувати клієнтам</th>
                <th scope="col">Категорії оновлювалися</th>
                <th scope="col">Категорії поточний статус</th>
                <th scope="col">Дитилі оновлювалися</th>
                <th scope="col">Дитилі поточний статус</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="parserSetting in parserSettings " :key="parserSetting.id">
                <th scope="row">{{ parserSetting.id }}</th>
                <td>{{ parserSetting.brand }}</td>
                <td>{{ parserSetting.car_models }}</td>
                <td>{{ parserSetting.year }}</td>
                <td><input class="form-check-input" type="checkbox" id="" disabled :checked="parserSetting.is_show"></td>
                <td>{{ parserSetting.category_parsing_at }}<span class="btn text-primary" @click="updateCategoryParsingStatus(parserSetting.id)">Оновиты Категорії та Дитилі</span></td>
                <td>{{ parserSetting.category_parsing_status }}</td>
                <td>{{ parserSetting.detail_parsing_at }}<span class="btn text-primary" @click="updateDetailParsingStatus(parserSetting.id)">Оновиты Дитилі</span></td>
                <td>{{ parserSetting.detail_parsing_status }}</td>
                <th scope="col" class="btn btn-info m-1" @click="showEditModal(parserSetting.id)">Edit</th>
                <th scope="col" class="btn btn-dark text-danger" @click="deleteSetting(parserSetting.id)">Delete</th>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script>

import axios from 'axios'

export default {
    data() {
        return {
            parserSettings: [],
            editableId: null,
            setting: {}
        }
    },
    methods: {
        async loadParserSettings() {
            let response = await axios.get('/admin/settings/')
            if (response.status) {
                this.parserSettings = response.data.data
            } else {
                alert('Something went wrong try again later.')
            }
        },
        async deleteSetting(id) {
            let response = await axios.delete(`/admin/settings/${id}`)
            if (response.status) {
                alert(response.data.message)
                this.parserSettings = this.parserSettings.filter((setting) => setting.id !== id)
            } else {
                alert('Something went wrong try again later.')
            }
        },
        async updateCategoryParsingStatus(id){
            let response = await axios.get(`/admin/settings/${id}/update_category_parsing_status`)
            if (response.status) {
                alert(response.data.message)
                this.loadParserSettings()
            } else {
                alert('Something went wrong try again later.')
            }
        },
        async updateDetailParsingStatus(id){
            let response = await axios.get(`/admin/settings/${id}/update_detail_parsing_status`)
            if (response.status) {
                alert(response.data.message)
                this.loadParserSettings()
            } else {
                alert('Something went wrong try again later.')
            }
        },
        showEditModal(id) {
            this.editableId = id
            this.setting = this.parserSettings.find((setting) => setting.id === id)
            this.showModal()
        },
        showModal() {
            this.$root.$emit('bv::show::modal', 'editSetting')
        },
        hideModal() {
            this.$root.$emit('bv::hide::modal', 'editSetting')
        },
        setEditableNull() {
            this.editableId = null
            this.setting = {}
        },
        async updateSetting() {
            if (this.editableId == null) {
                let response = await axios.post('/admin/settings', this.setting)
                alert(response.data.message)
                await this.loadParserSettings()
            } else {
                let response = await axios.put(`/admin/settings/${this.editableId}`, this.setting)
            }
            this.setEditableNull()
        }

    },
    async mounted() {
        await this.loadParserSettings()
    }
}
</script>
