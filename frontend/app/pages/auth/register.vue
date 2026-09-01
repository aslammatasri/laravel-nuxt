<script setup lang="ts">
definePageMeta({
    middleware: "guest",
});

const route = useRoute();
const authStore = useAuthStore();

const role = computed(
    () => (route.query.role as "creator" | "brand") || "creator",
);

const form = reactive({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: role.value,
});

async function handleRegister() {
    await authStore.register(form);
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === "Enter") handleRegister();
}

const isBrand = computed(() => role.value === "brand");
const btnClass = computed(() =>
    isBrand.value ? "rg-btn-brand" : "rg-btn-creator",
);
</script>

<template>
    <div class="rg-page">
        <div class="rg-bg-grid"></div>
        <div class="rg-blob rg-blob-1"></div>
        <div class="rg-blob rg-blob-2"></div>

        <div class="rg-inner">
            <!-- Header -->
            <div class="rg-header">
                <NuxtLink to="/" class="rg-logo">AffiliateMY</NuxtLink>
                <div class="rg-role-badge">
                    <AppIcon :name="role === 'brand' ? 'creators' : 'video'" class="rg-role-icon" />
                    {{ role === "brand" ? "Brand Account" : "Creator Account" }}
                </div>
                <h2 class="rg-title">Create your account</h2>
                <p class="rg-sub">
                    Wrong type?
                    <NuxtLink to="/auth/register-type" class="rg-link"
                        >Go back</NuxtLink
                    >
                </p>
            </div>

            <!-- Form card -->
            <div class="rg-card">
                <!-- Error -->
                <div v-if="authStore.error" class="rg-error">
                    {{ authStore.error }}
                </div>

                <div class="rg-field-group" @keydown="handleKeydown">
                    <!-- Name -->
                    <div class="rg-field">
                        <label class="rg-label">Full Name</label>
                        <div class="rg-input-wrap">
                            <AppIcon name="user" class="rg-input-icon" />
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Ahmad bin Ali"
                                class="rg-input"
                                autocomplete="name"
                            />
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="rg-field">
                        <label class="rg-label">Email</label>
                        <div class="rg-input-wrap">
                            <AppIcon name="mail" class="rg-input-icon" />
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="you@example.com"
                                class="rg-input"
                                autocomplete="email"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="rg-field">
                        <label class="rg-label">Password</label>
                        <div class="rg-input-wrap">
                            <AppIcon name="lock" class="rg-input-icon" />
                            <input
                                v-model="form.password"
                                type="password"
                                placeholder="Min 8 characters"
                                class="rg-input"
                                autocomplete="new-password"
                            />
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="rg-field">
                        <label class="rg-label">Confirm Password</label>
                        <div class="rg-input-wrap">
                            <AppIcon name="check" class="rg-input-icon" />
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="Repeat your password"
                                class="rg-input"
                                autocomplete="new-password"
                            />
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        @click="handleRegister"
                        :disabled="authStore.isLoading"
                        class="rg-btn"
                        :class="[
                            btnClass,
                            { 'rg-btn-loading': authStore.isLoading },
                        ]"
                    >
                        <span
                            v-if="authStore.isLoading"
                            class="rg-spinner"
                        ></span>
                        <span v-else>Create Account</span>
                    </button>
                </div>

                <p class="rg-footer-text">
                    Already have an account?
                    <NuxtLink to="/auth/login" class="rg-link">Log in</NuxtLink>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

.rg-page {
    font-family: "Inter", sans-serif;
    min-height: 100vh;
    background: #f8f7f4;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
}

.rg-bg-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}

.rg-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
}

.rg-blob-1 {
    width: 400px;
    height: 400px;
    background: rgba(79, 70, 229, 0.08);
    top: -150px;
    right: -80px;
}

.rg-blob-2 {
    width: 350px;
    height: 350px;
    background: rgba(71, 85, 105, 0.08);
    bottom: -100px;
    left: -60px;
}

.rg-inner {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
    animation: rgFadeUp 0.6s ease both;
}

.rg-header {
    text-align: center;
    margin-bottom: 2rem;
}

.rg-logo {
    font-weight: 800;
    font-size: 1.5rem;
    color: #1a1a18;
    text-decoration: none;
    letter-spacing: -0.02em;
    display: inline-block;
    margin-bottom: 1rem;
}

.rg-role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: #555;
    background: rgba(0, 0, 0, 0.04);
    padding: 0.3rem 0.9rem;
    border-radius: 100px;
    margin-bottom: 0.75rem;
}

.rg-role-icon {
    width: 14px;
    height: 14px;
    color: #4f46e5;
}

.rg-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a18;
    letter-spacing: -0.02em;
}

.rg-sub {
    color: #888;
    font-size: 0.85rem;
    margin-top: 0.3rem;
}

.rg-link {
    color: #4f46e5;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}

.rg-link:hover {
    opacity: 0.75;
}

.rg-card {
    background: #fff;
    border-radius: 24px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 2.25rem 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
}

.rg-error {
    background: #fef2f2;
    color: #dc2626;
    font-size: 0.85rem;
    border-radius: 14px;
    padding: 0.85rem 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(220, 38, 38, 0.1);
}

.rg-field-group {
    display: flex;
    flex-direction: column;
    gap: 1.1rem;
}

.rg-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.rg-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #555;
    letter-spacing: 0.01em;
}

.rg-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.rg-input-icon {
    position: absolute;
    left: 1rem;
    width: 16px;
    height: 16px;
    color: #9ca3af;
    pointer-events: none;
}

.rg-input {
    width: 100%;
    border: 1.5px solid #e5e5e3;
    border-radius: 14px;
    padding: 0.85rem 1rem 0.85rem 2.75rem;
    font-size: 0.9rem;
    font-family: "Inter", sans-serif;
    color: #1a1a18;
    background: #fafafa;
    transition:
        border-color 0.2s,
        background 0.2s,
        box-shadow 0.2s;
    outline: none;
}

.rg-input:focus {
    border-color: #4f46e5;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
}

.rg-input::placeholder {
    color: #bbb;
}

.rg-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: #fff;
    font-weight: 600;
    font-size: 0.9rem;
    font-family: "Inter", sans-serif;
    padding: 0.9rem;
    border: none;
    border-radius: 14px;
    cursor: pointer;
    transition:
        transform 0.2s,
        box-shadow 0.2s,
        opacity 0.2s;
    margin-top: 0.25rem;
}

.rg-btn-creator {
    background: #4f46e5;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
}

.rg-btn-creator:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.45);
}

.rg-btn-brand {
    background: #0f172a;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.rg-btn-brand:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.rg-btn:active:not(:disabled) {
    transform: translateY(0);
}

.rg-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.rg-spinner {
    width: 20px;
    height: 20px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: rgSpin 0.6s linear infinite;
}

.rg-footer-text {
    text-align: center;
    margin-top: 1.75rem;
    font-size: 0.85rem;
    color: #888;
}

@keyframes rgFadeUp {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes rgSpin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 480px) {
    .rg-card {
        padding: 1.75rem 1.25rem;
    }
}
</style>
