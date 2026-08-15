<script setup lang="ts">
definePageMeta({
    middleware: "guest",
});

const authStore = useAuth();

const form = reactive({
    email: "",
    password: "",
});

async function handleLogin() {
    await authStore.login(form);
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === "Enter") handleLogin();
}
</script>

<template>
    <div class="login-page">
        <div class="lg-bg-grid"></div>
        <div class="lg-blob lg-blob-1"></div>
        <div class="lg-blob lg-blob-2"></div>

        <div class="lg-inner">
            <!-- Header -->
            <div class="lg-header">
                <NuxtLink to="/" class="lg-logo">AffiliateMY</NuxtLink>
                <h2 class="lg-title">Welcome back</h2>
                <p class="lg-sub">Log in to your account</p>
            </div>

            <!-- Form card -->
            <div class="lg-card">
                <!-- Error -->
                <div v-if="authStore.error" class="lg-error">
                    {{ authStore.error }}
                </div>

                <div class="lg-field-group" @keydown="handleKeydown">
                    <!-- Email -->
                    <div class="lg-field">
                        <label class="lg-label">Email</label>
                        <div class="lg-input-wrap">
                            <span class="lg-input-icon">📧</span>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="you@example.com"
                                class="lg-input"
                                autocomplete="email"
                            />
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="lg-field">
                        <div
                            style="
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                            "
                        >
                            <label class="lg-label">Password</label>
                            <NuxtLink
                                to="/auth/forgot-password"
                                class="lg-link"
                                style="font-size: 0.78rem"
                                >Forgot password?</NuxtLink
                            >
                        </div>
                        <div class="lg-input-wrap">
                            <span class="lg-input-icon">🔒</span>
                            <input
                                v-model="form.password"
                                type="password"
                                placeholder="Your password"
                                class="lg-input"
                                autocomplete="current-password"
                            />
                        </div>
                    </div>

                    <!-- Submit -->
                    <button
                        @click="handleLogin"
                        :disabled="authStore.isLoading"
                        class="lg-btn"
                        :class="{ 'lg-btn-loading': authStore.isLoading }"
                    >
                        <span
                            v-if="authStore.isLoading"
                            class="lg-spinner"
                        ></span>
                        <span v-else>Log In</span>
                    </button>
                </div>

                <p class="lg-footer-text">
                    Don't have an account?
                    <NuxtLink to="/auth/register-type" class="lg-link"
                        >Sign up</NuxtLink
                    >
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

.login-page {
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

.lg-bg-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}

.lg-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
}

.lg-blob-1 {
    width: 400px;
    height: 400px;
    background: rgba(79, 70, 229, 0.08);
    top: -150px;
    left: -80px;
}

.lg-blob-2 {
    width: 350px;
    height: 350px;
    background: rgba(71, 85, 105, 0.08);
    bottom: -100px;
    right: -60px;
}

.lg-inner {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
    animation: lgFadeUp 0.6s ease both;
}

.lg-header {
    text-align: center;
    margin-bottom: 2rem;
}

.lg-logo {
    font-weight: 800;
    font-size: 1.5rem;
    color: #1a1a18;
    text-decoration: none;
    letter-spacing: -0.02em;
    display: inline-block;
    margin-bottom: 1rem;
}

.lg-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #1a1a18;
    letter-spacing: -0.02em;
}

.lg-sub {
    color: #888;
    font-size: 0.9rem;
    margin-top: 0.3rem;
}

.lg-card {
    background: #fff;
    border-radius: 24px;
    border: 1px solid rgba(0, 0, 0, 0.06);
    padding: 2.25rem 2rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.04);
}

.lg-error {
    background: #fef2f2;
    color: #dc2626;
    font-size: 0.85rem;
    border-radius: 14px;
    padding: 0.85rem 1rem;
    margin-bottom: 1.5rem;
    border: 1px solid rgba(220, 38, 38, 0.1);
}

.lg-field-group {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.lg-field {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.lg-label {
    font-size: 0.8rem;
    font-weight: 600;
    color: #555;
    letter-spacing: 0.01em;
}

.lg-input-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.lg-input-icon {
    position: absolute;
    left: 1rem;
    font-size: 1rem;
    line-height: 1;
    pointer-events: none;
}

.lg-input {
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

.lg-input:focus {
    border-color: #4f46e5;
    background: #fff;
    box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.08);
}

.lg-input::placeholder {
    color: #bbb;
}

.lg-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: #4f46e5;
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
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
    margin-top: 0.25rem;
}

.lg-btn:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.45);
}

.lg-btn:active:not(:disabled) {
    transform: translateY(0);
}

.lg-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.lg-spinner {
    width: 20px;
    height: 20px;
    border: 2.5px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: lgSpin 0.6s linear infinite;
}

.lg-footer-text {
    text-align: center;
    margin-top: 1.75rem;
    font-size: 0.85rem;
    color: #888;
}

.lg-link {
    color: #4f46e5;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}

.lg-link:hover {
    opacity: 0.75;
}

@keyframes lgFadeUp {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes lgSpin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 480px) {
    .lg-card {
        padding: 1.75rem 1.25rem;
    }
}
</style>
