<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { EyeIcon, EyeOffIcon, LockIcon, MailIcon } from 'lucide-vue-next';
import { ref } from 'vue';
import logoImage from '@/../../resources/images/dswd-caraga-logo.jpg';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();

const showPassword = ref(false);
</script>

<template>
    <Head title="Sign In" />

    <div
        class="flex h-dvh flex-col bg-slate-100 xl:h-auto xl:min-h-screen xl:items-center xl:justify-center xl:px-6 xl:py-8 dark:bg-slate-950"
    >
        <div
            class="flex w-full flex-1 animate-fade-in-up flex-col items-center xl:max-w-lg xl:flex-none"
        >
            <!-- Blue gradient header -->
            <div
                class="flex w-full flex-col items-center bg-gradient-to-r from-blue-600 via-blue-700 to-blue-800 px-6 py-6 text-center shadow-lg dark:from-blue-700 dark:via-blue-800 dark:to-blue-900"
            >
                <div
                    class="mb-3 h-16 w-16 overflow-hidden rounded-full border-4 border-white/30 bg-white shadow-lg xl:h-20 xl:w-20"
                >
                    <img
                        :src="logoImage"
                        alt="DSWD Caraga Logo"
                        class="h-full w-full object-cover"
                    />
                </div>
                <h1
                    class="text-sm font-extrabold tracking-[0.2em] text-white uppercase sm:text-lg"
                >
                    <span class="block">ECT Beneficiaries</span>
                    <span class="block">Validation</span>
                </h1>
                <div class="mx-auto mt-3 h-0.5 w-10 bg-white/30"></div>
            </div>

            <!-- Form card -->
            <Form
                v-bind="store.form()"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex w-full flex-1 flex-col bg-white px-6 py-5 shadow-lg sm:px-10 xl:flex-none xl:border xl:border-t-0 xl:border-slate-200 dark:border-slate-700 dark:bg-slate-900"
            >
                <h2
                    class="mb-0.5 text-lg font-semibold text-slate-900 sm:text-xl dark:text-white"
                >
                    Welcome back
                </h2>
                <p class="mb-5 text-sm text-slate-500 dark:text-slate-400">
                    Sign in to your account to continue
                </p>

                <!-- Status message -->
                <div
                    v-if="status"
                    class="mb-4 flex items-center gap-3 border-l-4 border-green-500 bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/30 dark:text-green-300"
                >
                    {{ status }}
                </div>

                <!-- Email field -->
                <div class="mb-4">
                    <label
                        for="email"
                        class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Email Address
                    </label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute top-0 left-0 flex h-11 w-11 items-center justify-center border-r border-slate-200 text-slate-400 dark:border-slate-700"
                        >
                            <MailIcon class="h-4 w-4" />
                        </span>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="email"
                            placeholder="you@example.com"
                            value="test@example.com"
                            class="h-11 w-full border bg-slate-50 pr-4 pl-14 text-sm text-slate-900 transition-all focus:bg-white focus:ring-2 focus:outline-none dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900"
                            :class="
                                errors.email
                                    ? 'border-red-400 focus:border-red-500 focus:ring-red-200 dark:border-red-600 dark:focus:ring-red-800'
                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-200 dark:border-slate-700 dark:focus:border-blue-400 dark:focus:ring-blue-800'
                            "
                        />
                    </div>
                    <InputError :message="errors.email" />
                </div>

                <!-- Password field -->
                <div class="mb-4">
                    <label
                        for="password"
                        class="mb-1.5 block text-xs font-semibold tracking-wider text-slate-500 uppercase dark:text-slate-400"
                    >
                        Password
                    </label>
                    <div class="relative">
                        <span
                            class="pointer-events-none absolute top-0 left-0 flex h-11 w-11 items-center justify-center border-r border-slate-200 text-slate-400 dark:border-slate-700"
                        >
                            <LockIcon class="h-4 w-4" />
                        </span>
                        <input
                            id="password"
                            :type="showPassword ? 'text' : 'password'"
                            name="password"
                            required
                            :tabindex="2"
                            autocomplete="current-password"
                            placeholder="Enter your password"
                            value="password"
                            class="h-11 w-full border bg-slate-50 pr-11 pl-14 text-sm text-slate-900 transition-all focus:bg-white focus:ring-2 focus:outline-none dark:bg-slate-950 dark:text-white dark:focus:bg-slate-900"
                            :class="
                                errors.password
                                    ? 'border-red-400 focus:border-red-500 focus:ring-red-200 dark:border-red-600 dark:focus:ring-red-800'
                                    : 'border-slate-200 focus:border-blue-500 focus:ring-blue-200 dark:border-slate-700 dark:focus:border-blue-400 dark:focus:ring-blue-800'
                            "
                        />
                        <button
                            type="button"
                            :tabindex="6"
                            class="absolute top-0 right-0 flex h-11 w-11 items-center justify-center text-slate-400 transition-colors hover:text-slate-600 dark:hover:text-slate-300"
                            @click="showPassword = !showPassword"
                        >
                            <EyeOffIcon v-if="showPassword" class="h-4 w-4" />
                            <EyeIcon v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <InputError :message="errors.password" />
                </div>

                <!-- Remember me + Forgot password -->
                <div class="mb-5 flex items-center justify-between">
                    <label
                        for="remember"
                        class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-400"
                    >
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        Remember me
                    </label>
                    <TextLink
                        v-if="canResetPassword"
                        :href="request()"
                        class="text-sm font-medium text-blue-600 transition-colors hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        :tabindex="5"
                    >
                        Forgot password?
                    </TextLink>
                </div>

                <!-- Submit button -->
                <button
                    type="submit"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                    class="relative flex w-full items-center justify-center gap-2 bg-blue-600 px-5 py-3 text-sm font-semibold tracking-widest text-white uppercase shadow-md transition-all hover:bg-blue-700 hover:shadow-lg focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50 dark:focus:ring-offset-slate-900"
                >
                    <Spinner v-if="processing" class="text-white" />
                    {{ processing ? 'Signing in...' : 'Sign In' }}
                </button>

                <div
                    v-if="canRegister"
                    class="mt-4 text-center text-sm text-slate-500 dark:text-slate-400"
                >
                    Don't have an account?
                    <TextLink
                        :href="register()"
                        :tabindex="7"
                        class="font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        Sign up
                    </TextLink>
                </div>

                <!-- Developed by footer -->
                <div class="mt-auto pt-4 text-center">
                    <div
                        class="mx-auto mb-2 h-px w-16 bg-slate-300 dark:bg-slate-700"
                    ></div>
                    <p class="text-xs text-slate-400 dark:text-slate-500">
                        Developed by Arvin B. Edubas
                    </p>
                    <p
                        class="text-[10px] font-medium tracking-widest text-slate-400/70 uppercase dark:text-slate-600"
                    >
                        VSquared Technologies
                    </p>
                </div>
            </Form>
        </div>
    </div>
</template>
