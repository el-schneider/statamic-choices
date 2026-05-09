const l={name:"ChoicesFieldtype",mixins:[Fieldtype],props:{handle:{type:String,required:!0},value:{type:[String,Array,Number,Boolean],default:null},meta:{type:Object,default:()=>({})},config:{type:Object,default:()=>({})},readOnly:{type:Boolean,default:!1},namePrefix:{type:String,default:null},showFieldPreviews:{type:Boolean,default:!1}},computed:{options(){var i;return((i=this.meta)==null?void 0:i.options)??[]},isMultiple(){var i;return((i=this.config)==null?void 0:i.mode)==="multiple"},isImageVariant(){var i;return((i=this.config)==null?void 0:i.variant)==="image"},inputType(){return this.isMultiple?"checkbox":"radio"},inputName(){const e=this,i=e.name??(e.namePrefix?`${e.namePrefix}[${e.handle}]`:e.handle);return e.isMultiple?`${i}[]`:i},selectedValues(){const e=this;return e.isMultiple?Array.isArray(e.value)?e.sortValues(e.value.map(String)):[]:e.value===null||e.value===void 0||e.value===""?[]:[String(e.value)]},cardWidthClass(){var s;const i=String(((s=this.config)==null?void 0:s.card_width)??100);return["100","50","33","25","20"].includes(i)?`choices-grid--card-width-${i}`:"choices-grid--card-width-100"},variantClass(){return this.isImageVariant?"choices-grid--image-variant":"choices-grid--content-variant"},imageAspectClass(){var s;const i=String(((s=this.config)==null?void 0:s.image_aspect)??"1/1");return i==="4/3"?"choices-card--aspect-4-3":i==="16/9"?"choices-card--aspect-16-9":"choices-card--aspect-1-1"},isReadOnly(){var i,s;const e=this;return e.readOnly===!0||((i=e.config)==null?void 0:i.visibility)==="read_only"||((s=e.config)==null?void 0:s.visibility)==="computed"},isDisabled(){var i;return!!((i=this.config)!=null&&i.disabled)},replicatorPreview(){const e=this;if(e.showFieldPreviews)return e.selectedValues.map(i=>{var s;return((s=e.options.find(t=>String(t.value)===i))==null?void 0:s.label)??i}).join(", ")}},watch:{replicatorPreview:{immediate:!0,handler(e){const i=this;i.showFieldPreviews&&i.$emit("replicator-preview-updated",e)}}},methods:{isSelected(e){return this.selectedValues.includes(String(e))},handleInput(e,i){const s=this;if(!(s.isReadOnly||s.isDisabled)){if(!s.isMultiple){s.update(e);return}s.setMultipleSelection(e,i.target.checked)}},handleCardClick(e,i){const s=this;if(!(s.isReadOnly||s.isDisabled||i.target.closest('a[href], button, input, select, textarea, [role="button"]'))){if(i.preventDefault(),!s.isMultiple){s.update(e);return}s.setMultipleSelection(e,!s.isSelected(e))}},setMultipleSelection(e,i){const s=this;if(s.isReadOnly||s.isDisabled)return;const t=new Set(s.selectedValues);i?t.add(String(e)):t.delete(String(e)),s.update(s.sortValues([...t]))},sortValues(e){return this.options.filter(s=>e.includes(String(s.value))).map(s=>String(s.value))},focus(){var i;(i=this.$el.querySelector("input"))==null||i.focus()}},template:`
        <div class="choices-fieldtype choices-fieldtype--v5">
            <div
                class="choices-grid"
                :class="[cardWidthClass, variantClass]"
                :role="isMultiple ? 'group' : 'radiogroup'"
                :aria-label="config.display || handle"
            >
                <label
                    v-for="option in options"
                    :key="option.value"
                    class="choices-card-shell"
                    :class="{
                        'choices-card-shell--selected': isSelected(option.value),
                        'choices-card-shell--disabled': isReadOnly || isDisabled
                    }"
                    :title="isImageVariant ? option.label : null"
                    @click="handleCardClick(option.value, $event)"
                >
                    <input
                        class="choices-card__input"
                        :type="inputType"
                        :name="inputName"
                        :value="option.value"
                        :checked="isSelected(option.value)"
                        :disabled="isReadOnly || isDisabled"
                        :aria-label="option.label"
                        @change="handleInput(option.value, $event)"
                    />

                    <span
                        class="choices-card"
                        :class="[
                            isImageVariant ? 'choices-card--image' : 'choices-card--content',
                            isImageVariant ? imageAspectClass : null
                        ]"
                    >
                        <span class="choices-card__control" aria-hidden="true">
                            <span v-if="isMultiple" class="choices-card__checkbox">
                                <svg
                                    v-if="isSelected(option.value)"
                                    viewBox="0 0 10 8"
                                    fill="none"
                                    xmlns="http://www.w3.org/2000/svg"
                                    class="choices-card__checkmark"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M9 1L3.5 6.5L1 4"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            <span v-else class="choices-card__radio">
                                <span v-if="isSelected(option.value)" class="choices-card__radio-dot"></span>
                            </span>
                        </span>

                        <template v-if="isImageVariant">
                            <img
                                v-if="option.image_url"
                                class="choices-card__image-full"
                                :src="option.image_url"
                                alt=""
                                aria-hidden="true"
                            />
                            <span v-else class="choices-card__image-fallback">{{ option.label }}</span>
                        </template>

                        <template v-else>
                            <img
                                v-if="!option.use_html && option.image_url"
                                class="choices-card__image"
                                :src="option.image_url"
                                :alt="option.image_alt || option.label"
                            />

                            <span class="choices-card__body">
                                <span class="choices-card__label">{{ option.label }}</span>
                                <span v-if="option.use_html && option.html" class="choices-card__html" v-html="option.html"></span>
                                <span v-else-if="option.description" class="choices-card__description">{{ option.description }}</span>
                            </span>
                        </template>
                    </span>
                </label>
            </div>
        </div>
    `};var a;(a=Statamic.booting)==null||a.call(Statamic,()=>{var e;(e=Statamic.$components)==null||e.register("choices-fieldtype",l)});
