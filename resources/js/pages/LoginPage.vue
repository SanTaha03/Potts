<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/authStore";
import { Icon } from "@iconify/vue";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const showPassword = ref(false);

const form = reactive({
    email: "tech@potts.app",
    password: "password",
    remember: false,
});

const loading = computed(() => authStore.loading);
const error = computed(() => authStore.error);

async function submit() {
    if (loading.value) {
        return;
    }

    try {
        await authStore.login({
            email: form.email,
            password: form.password,
            remember: form.remember,
        });

        const redirect =
            typeof route.query.redirect === "string"
                ? route.query.redirect
                : "/";
        router.replace(redirect || "/");
    } catch {
        // error already handled by the store
    }
}
</script>

<template>
    <div
        class="min-h-screen bg-[#FDFEFE] flex flex-col items-center justify-center relative overflow-hidden px-6"
    >
        <!-- Background pattern -->
        <div
            class="absolute inset-0 pointer-events-none opacity-[0.03]"
            style="
                background-image: url(&quot;data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48cGF0aCBkPSJNMjAgMjBMMCAwTTQwIDQwTDIwIDIwIiBzdHJva2U9IiMwMDAiIHN0cm9rZS13aWR0aD0iMSIvPjwvc3ZnPg==&quot;);
            "
        ></div>

        <!-- Decoration Gradient Blob -->
        <div
            class="absolute top-10 right-0 w-64 h-64 bg-[#D0F471] rounded-full filter blur-[80px] opacity-20 -z-10"
        ></div>
        <div
            class="absolute bottom-10 left-0 w-64 h-64 bg-[#EDE5FF] rounded-full filter blur-[80px] opacity-20 -z-10"
        ></div>

        <div class="w-full max-w-sm">
            <!-- Header -->
            <div class="mb-10 text-center">
                <!-- Logo Placeholder or Icon -->
                <div
                    class="mx-auto mb-6 flex h-40 w-fit items-center justify-center transform -rotate-6"
                >
                    <img
                        src="/public/images/plante_stickers.png"
                        alt="Logo"
                        class="h-full w-full object-contain"
                    />
                </div>

                <h1 class="text-3xl font-bold text-[#1E1E1E]">Bienvenue</h1>
                <p class="mt-3 text-sm text-gray-500 font-medium">
                    Connectez-vous pour gérer vos missions et vos plantes.
                </p>
            </div>

            <form class="space-y-5" @submit.prevent="submit">
                <!-- Email Input -->
                <div class="space-y-2">
                    <label
                        for="email"
                        class="text-sm font-bold text-[#1E1E1E] ml-1"
                        >Email</label
                    >
                    <div class="relative">
                        <div
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
                        >
                            <Icon
                                icon="ph:envelope-simple-bold"
                                class="w-5 h-5"
                            />
                        </div>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            inputmode="email"
                            autocomplete="email"
                            required
                            placeholder="votre@email.com"
                            class="w-full rounded-2xl bg-[#F8F9FA] border border-transparent px-4 py-4 pl-12 text-sm font-medium text-[#1E1E1E] outline-none transition-all placeholder:text-gray-400 focus:bg-white focus:border-[#D0F471] focus:shadow-sm"
                        />
                    </div>
                </div>

                <!-- Password Input -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between ml-1">
                        <label
                            for="password"
                            class="text-sm font-bold text-[#1E1E1E]"
                            >Mot de passe</label
                        >
                    </div>
                    <div class="relative">
                        <div
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"
                        >
                            <Icon icon="ph:lock-key-bold" class="w-5 h-5" />
                        </div>
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            autocomplete="current-password"
                            required
                            placeholder="••••••••"
                            class="w-full rounded-2xl bg-[#F8F9FA] border border-transparent px-4 py-4 pl-12 pr-12 text-sm font-medium text-[#1E1E1E] outline-none transition-all placeholder:text-gray-400 focus:bg-white focus:border-[#D0F471] focus:shadow-sm"
                        />
                        <button
                            type="button"
                            @click="showPassword = !showPassword"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                        >
                            <Icon
                                :icon="
                                    showPassword
                                        ? 'ph:eye-slash-bold'
                                        : 'ph:eye-bold'
                                "
                                class="w-5 h-5"
                            />
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label
                        class="flex items-center gap-2 text-sm text-gray-600 font-medium cursor-pointer select-none"
                    >
                        <div class="relative flex items-center">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 bg-white transition-all checked:border-[#D0F471] checked:bg-[#D0F471]"
                            />
                            <Icon
                                icon="ph:check-bold"
                                class="absolute pointer-events-none opacity-0 peer-checked:opacity-100 text-[#1E1E1E] w-3.5 h-3.5 left-0.5"
                            />
                        </div>
                        Se souvenir de moi
                    </label>
                    <a
                        href="/forgot-password"
                        class="text-sm font-bold text-[#1E1E1E] hover:underline decoration-[#D0F471] underline-offset-4"
                    >
                        Mot de passe oublié ?
                    </a>
                </div>

                <!-- Error Message -->
                <div
                    v-if="error"
                    class="rounded-xl border border-red-100 bg-red-50 p-4 flex items-start gap-3 text-sm text-red-600"
                >
                    <Icon
                        icon="ph:warning-circle-bold"
                        class="w-5 h-5 shrink-0 mt-0.5"
                    />
                    <span>{{ error }}</span>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    :disabled="loading"
                    class="w-full mt-4 bg-[#D0F471] text-[#1E1E1E] py-4 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-transform active:scale-[0.98] disabled:opacity-70 disabled:cursor-not-allowed"
                >
                    <Icon
                        v-if="loading"
                        icon="ph:spinner-gap-bold"
                        class="animate-spin h-5 w-5"
                    />
                    <span v-else>Se connecter</span>
                    <Icon v-if="!loading" icon="ph:arrow-right-bold" />
                </button>
            </form>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500">
                    Pas encore de compte ?
                    <a
                        href="#"
                        class="font-bold text-[#1E1E1E] hover:underline decoration-[#D0F471] underline-offset-4"
                        >Contacter l'administrateur</a
                    >
                </p>
            </div>
        </div>
    </div>
</template>
