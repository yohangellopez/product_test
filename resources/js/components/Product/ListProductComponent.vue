<template>
    <div class="container">
        <div class="ml-auto mt-5">
            <b-button v-b-modal.modal-prevent-closing>Nuevo Producto</b-button>
        </div>
        <div class="container mt-3">
             <vue-good-table
                :columns="columns"
                :rows="products">
                <template slot="table-row" slot-scope="props">
                <span v-if="props.column.field == 'actions'">
                    <button @click="editRow(props.row.id)">Editar</button>
                    <button @click="Remove_Product(props.row.id)">Eliminar</button>
                </span>
                <span v-else-if="props.column.field == 'image'">
                    <b-img
                    thumbnail
                    height="50"
                    width="50"
                    fluid
                    :src="'/images/products/' + props.row.image"
                    alt="image"
                    ></b-img>
                </span>
                </template>

            </vue-good-table>
        </div>
        <b-modal
            id="modal-prevent-closing"
            ref="modal"
            title="Agregar producto"
            :modal-ok="false" >
            <div class="row justify-content-center mt-2">
                <b-form @submit.stop.prevent="Create_Product" md="8">
                    <b-form-group
                            label="Código del producto:"
                        >
                        <b-form-input
                            type="text"
                            v-model="product.ref"
                            placeholder="Ingrese el codigo de referencia"
                            required
                        ></b-form-input>
                    </b-form-group>

                    <b-form-group
                            label="Descripcion del producto:"
                        >
                        <b-form-input
                            type="text"
                            v-model="product.description"
                            placeholder="Ingrese la descripcion"
                            required
                        ></b-form-input>
                    </b-form-group>

                    <b-form-group
                            label="Precio del producto:"
                        >
                        <b-form-input
                            type="number"
                            v-model="product.price"
                            placeholder="Ingrese el precio"
                            required
                        ></b-form-input>
                    </b-form-group>
                    <div id="my-strictly-unique-vue-upload-multiple-image" style="display: flex; justify-content: center;">
                        <vue-upload-multiple-image
                        @upload-success="uploadImageSuccess"
                        @before-remove="beforeRemove"
                        @edit-image="editImage"
                        :data-images="images"
                        idUpload="myIdUpload"
                        editUpload="myIdEdit"
                        maxImage="1"
                        dragText="Elija una imagen"
                        browseText="Click para buscar imagen"
                        ></vue-upload-multiple-image>
                    </div>
                    <b-button class="justify-content-center mt-2" type="submit" variant="primary">Registrar</b-button>
                </b-form>
            </div>
        </b-modal>
    </div>
</template>

<script>
import VueUploadMultipleImage from 'vue-upload-multiple-image'
import { VueGoodTable } from 'vue-good-table';
import axios from 'axios';
    export default {
        components: {
            VueUploadMultipleImage,
            VueGoodTable,
        },
        mounted() {
            console.log('Component mounted.')
        },
        created(){
            axios
            .get('http://127.0.0.1:8000/api/product')
            .then((res) =>{
                this.products= res.data.products;
            })
        },
        data(){
            return{
                columns: [
                     {
                    label: "Imagen",
                    field: "image",
                    type: "image",
                    html: true,
                    tdClass: "text-left",
                    thClass: "text-left"
                    },
                    {
                    label: 'Referencia',
                    field: 'ref',
                    },
                    {
                    label: 'Descripcion',
                    field: 'description',
                    },
                     {
                    label: 'Precio',
                    field: 'price',
                    type: 'number',
                    },
                    {
                    label: 'Creado el',
                    field: 'created_at',
                    type: 'date',
                    dateInputFormat: 'yyyy-MM-dd',
                    dateOutputFormat: 'dd-MMM-yy',
                    },
                    {
                    label: 'Acciones',
                    field: 'actions',
                    sortable: false,
                    },
                   
                ],
                product:{
                    description: "",
                    ref: "",
                    price: 0,
                },
                data: new FormData(),
                images:[],
                products:[],
            }
        },
        methods:{
            uploadImageSuccess(formData, index, fileList) {
                console.log('data', formData, index, fileList)
                 this.images = fileList;
            },

            beforeRemove (index, done, fileList) {
                console.log('index', index, fileList)
                var r = confirm("remove image")
                if (r == true) {
                    done()
                } else {
                }
            },

            editImage (formData, index, fileList) {
                console.log('edit data', formData, index, fileList)
            },

            Create_Product(){
                var self = this;

                // append objet product
                Object.entries(self.product).forEach(([key, value]) => {
                    self.data.append(key, value);
                });

                //append array images
                if (self.images.length > 0) {
                    for (var k = 0; k < self.images.length; k++) {
                    Object.entries(self.images[k]).forEach(([key, value]) => {
                        self.data.append("images[" + k + "][" + key + "]", value);
                    });
                    }
                }
                axios
                .post("http://127.0.0.1:8000/api/product", self.data)
                .then(response => {
                    this.resetForm();
                 this.$bvToast.toast('Producto agregado', {
                    title: 'Success',
                    autoHideDelay: 5000
                });
                this.$bvModal.hide('modal-prevent-closing');
                })
            },
            resetForm(){
                this.product={
                  description: "",   
                    ref: "",
                    price: 0,
                }; 
                this.images=[]; 
            },
            Remove_Product(id) {
                this.$swal({
                    title: "Eliminar producto",
                    text: "Desea eliminar el producto?",
                    type: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    cancelButtonText: "Cancelar",
                    confirmButtonText:"Confirmar"
                }).then(result => {
                    if (result.value) {
                    axios
                        .delete("http://127.0.0.1:8000/api/product/" + id)
                        .then(() => {
                        this.$swal(
                            "Eliminado",
                            "Producto Eliminado",
                            "success"
                        );
                        })
                        .catch(() => {
                        this.$swal(
                            "Error al eliminar el producto",
                            "Error",
                            "warning"
                        );
                        });
                    }
                });
            },
        }
    }
</script>
