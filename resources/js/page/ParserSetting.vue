<template>
    <div class="container">
        <button type="button" class="btn btn-success mb-5" @click="showModal">Add new</button>
        <div>
            <b-modal id="editSetting"
                     title="Edit"
                     @hidden="setEditableNull"
                     @ok="updateSetting"
            >
                <form>
                    <div class="form-group">
                        <label for="brand">Brand</label>
                        <input type="text" v-model="setting.brand" class="form-control" id="brand" aria-describedby="emailHelp">
                    </div>
                    <div class="form-group">
                        <label for="year_from">Years from</label>
                        <input type="number" v-model="setting.year_from" class="form-control" id="year_from">
                    </div>
                    <div class="form-group">
                        <label for="year_to">Years to</label>
                        <input type="number" v-model="setting.year_to" class="form-control" id="year_to">
                    </div>
                </form>
            </b-modal>
        </div>

        <table class="table">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Brand</th>
                <th scope="col">Years from</th>
                <th scope="col">Years to</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="parserSetting in parserSettings " :key="parserSetting.id">
                <th scope="row">{{ parserSetting.id }}</th>
                <td>{{ parserSetting.brand }}</td>
                <td>{{ parserSetting.year_from }}</td>
                <td>{{ parserSetting.year_to }}</td>
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
