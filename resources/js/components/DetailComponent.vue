<template>
    <tr>
        <td><span v-html="detail.title"></span></td>
        <td><a href="" @click.prevent="showAnalogyDetail" title="Деталі аналоги">{{ detail.s_number }}</a></td>
        <td class="detail-desc">{{ detail.short_description }}</td>
        <td v-if="detail.isDisabled">Тимчасово недоступно</td>
        <td v-else-if="detail.stock == 0">Під замовлення,час доставки 14 – 25 днів</td>
        <td v-else>{{ detail.total_price_uah }} грн.</td>
        <td v-if="authUser" class="">{{ detail.stock }}</td>
        <td v-if="authUser">
            <span class="btn btn-info mb-1" @click="showDetailModal"> Редагуваты</span>
            <span class="btn btn-info mb-1" @click="showAddAnalogyDetailModal"> Додати аналогову деталь</span>
            <span class="btn btn-danger " @click="deleteDetail">Видалити</span>
        </td>

        <!--      START Analogy Details Modal-->
        <b-modal :id="'modal'+ detail.id " title="Сумісність" hide-footer>
            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col"></th>
                    <th scope="col"></th>
                    <th scope="col"></th>
                    <th scope="col" v-if="authUser"></th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="(analogyDetail , key) in detail.analogy_details" :key="detail.id +'analogyDetail'+analogyDetail.id"
                >
                    <td>{{ analogyDetail.brand }}</td>
                    <td v-if="analogyDetail.model">{{ analogyDetail.model }}</td>
                    <td v-if="analogyDetail.years">{{ analogyDetail.years }}</td>
                    <td v-if="authUser" @click="()=>deleteAnalogyDetail(analogyDetail.id)"><span class="btn btn-danger">Видалити</span></td>
                </tr>
                </tbody>
            </table>
        </b-modal>
        <!--      END Analogy Details Modal-->

        <!--   START     Update Price Modal-->
        <b-modal :id="'editPrice' + detail.id"
                 title="Edit"
                 @hidden=" newDetail = {}"
                 @ok="updatePrice"
        >
            <form>
                <div class="form-group">
                    <label for="newDetail-title">Назва</label>
                    <input type="text" v-model.trim="newDetail.title" class="form-control" id="newDetail-title">
                </div>
                <div class="form-group">
                    <label for="newDetail-short_description">Опис</label>
                    <textarea type="text" v-model.trim="newDetail.short_description" class="form-control" id="newDetail-short_description"></textarea>
                </div>
                <div class="form-group">
                    <label for="newDetail-stock">Залышок</label>
                    <input type="text" v-model.trim="newDetail.stock" class="form-control" id="newDetail-stock">
                </div>
                <div class="form-group">
                    <label for="price">Базова ціна (має бути більше 0 !!!) [$]</label>
                    <input type="text" v-model.trim="newDetail.price" class="form-control" id="price">
                </div>
                <div class="form-group">
                    <label for="us_shipping_price">Ціна доставки по США [$]</label>
                    <input type="text" v-model.trim="newDetail.us_shipping_price" class="form-control" id="us_shipping_price">
                </div>
                <div class="form-group">
                    <label for="ua_shipping_price">Ціна доставка в Україні [$]</label>
                    <input type="text" v-model.trim="newDetail.ua_shipping_price" class="form-control" id="ua_shipping_price">
                </div>
                <div class="form-group">
                    <label for="price_markup">Додана вартість [$]</label>
                    <input type="text" v-model.trim="newDetail.price_markup" class="form-control" id="price_markup">
                </div>

            </form>
        </b-modal>
        <!--      END  Update Price Modal-->

        <!--   START     Add Analogue Detail Modal-->
        <b-modal :id="'addAnalogyDetail' + detail.id"
                 title="Додати аналогову деталь"
                 @hidden=" analogyDetail = {}"
                 @ok="addAnalogyDetail"
        >
            <form>
                <div class="form-group">
                    <label for="analogy-brand">Бренд</label>
                    <input type="text" v-model.trim="analogyDetail.brand" class="form-control" id="analogy-brand">
                </div>
                <div class="form-group">
                    <label for="analogy-model">Модель</label>
                    <input type="text" v-model.trim="analogyDetail.model" class="form-control" id="analogy-model">
                </div>
                <div class="form-group">
                    <label for="analogy-year">Рік</label>
                    <input type="text" v-model.trim="analogyDetail.years" class="form-control" id="analogy-year">
                </div>
            </form>
        </b-modal>
        <!--      END  Add Analogue Detail Modal-->
    </tr>

</template>


<script>
import axios from 'axios'

export default {
    props: ['detail', 'authUser', 'updateDetails'],
    data() {
        return {
            analogyDetails: [],
            newDetail: {},
            analogyDetail: {},
        }
    },
    methods: {
        async loadAnalogyDetail() {
            let response = await axios.get(`/api/detail/${this.detail.id}/analogy-details`)
            if (response.status) {
                this.analogyDetails = response.data.data
                this.$bvModal.show('modal' + this.detail.id)
            } else {
                alert('Something went wrong try again later.')
            }
        },
        showAnalogyDetail() {
            this.$bvModal.show('modal' + this.detail.id)
        },
        hideAnalogyDetail() {
            this.$bvModal.hide('modal' + this.detail.id)
        },
        showDetailModal() {
            this.$root.$emit('bv::show::modal', 'editPrice' + this.detail.id)
            this.newDetail = {...this.detail}
        },
        hideDetailModal() {
            this.$root.$emit('bv::hide::modal', 'editPrice' + this.detail.id)
        },

        showAddAnalogyDetailModal() {
            this.$root.$emit('bv::show::modal', 'addAnalogyDetail' + this.detail.id)
        },
        hideAddAnalogyDetailModal() {
            this.$root.$emit('bv::hide::modal', 'addAnalogyDetail' + this.detail.id)
        },

        async updatePrice() {
            let response = await axios.put(`/api/detail/${this.detail.id}`, this.newDetail)
            alert(response.data.message)
            this.newDetail = {}
            this.hideDetailModal()
            this.updateDetails()
        },
        async addAnalogyDetail() {
            this.newDetail = {...this.detail}
            this.newDetail.analogy_details.push({...this.analogyDetail, id: Date.now()})
            let response = await axios.put(`/api/detail/${this.detail.id}`, {
                'analogy_details':  this.newDetail.analogy_details,
                'title': this.detail.title,
                's_number': this.detail.s_number,
                'category_id': this.detail.category_id,
            })
            alert(response.data.message)
            this.newDetail = {}
            this.updateDetails()
            this.hideAddAnalogyDetailModal()
            this.analogyDetail = {}
        },
        async deleteAnalogyDetail(id) {
            let analogy_details = this.detail.analogy_details.filter((item) => {
                return item.id != id
            })
            console.log('analogy_details', analogy_details)
            let response = await axios.put(`/api/detail/${this.detail.id}`, {
                'analogy_details': analogy_details,
                'title': this.detail.title,
                's_number': this.detail.s_number,
                'category_id': this.detail.category_id,
            })
            alert(response.data.message)
            this.newDetail = {}
            this.updateDetails()
        },
        async deleteDetail() {
            let response = await axios.delete(`/api/detail/${this.detail.id}`)
            alert(response.data.message)
            this.updateDetails()
        },
    },
    mounted() {
        console.log('authUser', this.authUser)
    }
}
</script>
