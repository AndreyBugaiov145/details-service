<template>
    <div >
        <h5 @click="loadCategory"
            v-b-hover="handleHover"
            :class="isHovered ?
            'text-primary category-block-border' : 'text-light category-block-border'"
            style="cursor:pointer;"

>
            <b-icon v-if="!showChildCategory || !showChildCategoryDetails" icon="folder-plus" scale="0.7" variant="primary" aria-hidden="true"></b-icon>
            <b-icon v-else icon="file-earmark-minus" scale="0.7" variant="primary" aria-hidden="true"></b-icon>
            <span  v-html="category.title"></span>
        </h5>
        <div class="ml-3" v-if="categories.length && showChildCategory">
            <CategoryComponent
                v-for="category in categories" :key="category.id"
                :category="category"
                :authUser="authUser"
            />
        </div>
        <div v-if="details.length && showChildCategoryDetails">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th scope="col">Называ</th>
                    <th scope="col">
                        серійний номер
                    </th>
                    <th scope="col">Опис</th>
                    <th scope="col">Залишок</th>
                    <th scope="col">ціна</th>
                    <th v-if="authUser" scope="col">Редагуваты</th>
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
            <span v-if="authUser" class="btn btn-info" @click="showAddDetailModal"> Додати деталь</span>

            <!--   START     Add  Detail Modal-->
            <div>
                <b-modal :id="'addDetail' + category.id"
                         title="Додаты деталь"
                         @hidden=" newDetail = {}"
                         @ok="addDetail"
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
                            <label for="newDetail-s_number">Серійний номер</label>
                            <input type="text" v-model.trim="newDetail.s_number" class="form-control" id="newDetail-s_number">
                        </div>
                        <div class="form-group">
                            <label for="newDetail-stock">Залышок</label>
                            <input type="text" v-model.trim="newDetail.stock" class="form-control" id="newDetail-stock">
                        </div>
                        <div class="form-group">
                            <label for="price">Базова ціна (має бути більше 0 !!!)[$]</label>
                            <input type="text" v-model.trim="newDetail.price" class="form-control" id="price">
                        </div>
                        <div class="form-group">
                            <label for="us_shipping_price">Ціна доставки з США[$]</label>
                            <input type="text" v-model.trim="newDetail.us_shipping_price" class="form-control" id="us_shipping_price">
                        </div>
                        <div class="form-group">
                            <label for="ua_shipping_price">Ціна доставки по Україні[$]</label>
                            <input type="text" v-model.trim="newDetail.ua_shipping_price" class="form-control" id="ua_shipping_price">
                        </div>
                        <div class="form-group">
                            <label for="price_markup">Надбавкова сума[$]</label>
                            <input type="text" v-model.trim="newDetail.price_markup" class="form-control" id="price_markup">
                        </div>
                    </form>
                </b-modal>
            </div>
            <!--   END     Add  Detail Modal-->
        </div>
    </div>
</template>


<script>
import axios from 'axios'

export default {
    props: ['category',"authUser"],
    data() {
        return {
            isHovered: false,
            categories: [],
            details: [],
            newDetail: {},
            showChildCategory: false,
            showChildCategoryDetails: false,
        }
    },
    methods: {
        handleHover(hovered) {
            this.isHovered = hovered
        },
        async loadCategory() {
            if (!this.categories.length && !this.details.length) {
                let response = await axios.get(`/api/category/children/${this.category.id}`)
                if (response.status) {
                    this.categories = response.data.data
                    if (this.categories.length) {
                        this.showChildCategory = true
                        this.showChildCategoryDetails = true
                    } else {
                        this.loadDetails()
                    }
                } else {
                    alert('Something went wrong try again later.')
                }
            }else {
                this.showChildCategory = !this.showChildCategory
                this.showChildCategoryDetails = !this.showChildCategoryDetails
            }
        },
        async loadDetails() {
            if (!this.details.length) {
                let response = await axios.get(`/api/detail/category-details/${this.category.id}`)
                if (response.status) {
                    this.details = response.data.data
                    this.showChildCategory = true
                    this.showChildCategoryDetails = true
                    console.log(this.details)
                } else {
                    alert('Something went wrong try again later.')
                }
            } else {
                alert('loadDetails togle')
                this.showChildCategory = !this.showChildCategory
                this.showChildCategoryDetails = !this.showChildCategoryDetails
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

        async addDetail() {
            let response = await axios.post(`/api/detail`, {
                ...this.newDetail,
                category_id: this.category.id
            })
            alert(response.data.message)
            this.newDetail = {}
            this.hideAddDetailModal()
            this.updateDetails()
        },

        showAddDetailModal() {
            this.$root.$emit('bv::show::modal','addDetail' + this.category.id)
            this.newDetail = {...this.detail}
        },
        hideAddDetailModal() {
            this.$root.$emit('bv::hide::modal', 'addDetail' +  this.category.id)
        },
    },
    mounted() {
    }
}
</script>
