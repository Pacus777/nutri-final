<script setup>
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import FormSection from '@/Components/UI/FormSection.vue';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    roles: {
        type: Array,
        default: () => [],
    },
});

const toggleRol = (codRol) => {
    if (props.form.roles.includes(codRol)) {
        props.form.roles = props.form.roles.filter((id) => id !== codRol);
        return;
    }

    props.form.roles.push(codRol);
};
</script>

<template>
    <FormSection title="Roles">
        <InputLabel value="Roles asignados" />

        <div class="mt-2 grid grid-cols-1 gap-3 md:grid-cols-2">
            <label
                v-for="rol in roles"
                :key="rol.cod_roles"
                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3"
            >
                <input
                    type="checkbox"
                    :value="rol.cod_roles"
                    :checked="form.roles.includes(rol.cod_roles)"
                    @change="toggleRol(rol.cod_roles)"
                >

                <span>{{ rol.nom_rol }}</span>
            </label>
        </div>

        <InputError class="mt-2" :message="form.errors.roles" />
    </FormSection>
</template>