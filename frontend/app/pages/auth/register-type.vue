<script setup lang="ts">
definePageMeta({
    middleware: "guest",
});
</script>

<template>
    <div class="register-type">
        <div class="rt-bg-grid"></div>
        <div class="rt-blob rt-blob-1"></div>
        <div class="rt-blob rt-blob-2"></div>

        <div class="rt-inner">
            <!-- Logo -->
            <div class="rt-header">
                <NuxtLink to="/" class="rt-logo">AffiliateMY</NuxtLink>
                <p class="rt-sub">Choose your path</p>
            </div>

            <!-- Cards -->
            <div class="rt-cards">
                <!-- Creator Card -->
                <NuxtLink
                    to="/auth/register?role=creator"
                    class="rt-card rt-card-creator"
                >
                    <div class="rt-card-bg"></div>
                    <div class="rt-card-content">
                        <div class="rt-icon-wrap">
                            <AppIcon name="video" class="rt-icon" />
                        </div>
                        <h2 class="rt-card-title">I'm a Creator</h2>
                        <p class="rt-card-desc">
                            Browse products, apply to campaigns and earn
                            commissions promoting brands to your audience
                        </p>
                        <div class="rt-chip-list">
                            <span class="rt-chip">Free to join</span>
                            <span class="rt-chip">No minimum followers</span>
                            <span class="rt-chip">Weekly payouts</span>
                        </div>
                        <div class="rt-btn rt-btn-creator">
                            Join as Creator
                            <span class="rt-btn-arrow">→</span>
                        </div>
                    </div>
                </NuxtLink>

                <!-- Brand Card -->
                <NuxtLink
                    to="/auth/register?role=brand"
                    class="rt-card rt-card-brand"
                >
                    <div class="rt-card-bg"></div>
                    <div class="rt-card-content">
                        <div class="rt-icon-wrap">
                            <AppIcon name="creators" class="rt-icon" />
                        </div>
                        <h2 class="rt-card-title">I'm a Brand</h2>
                        <p class="rt-card-desc">
                            List your products, set commission rates and find
                            the right creators to promote them
                        </p>
                        <div class="rt-chip-list">
                            <span class="rt-chip">Pay per sale</span>
                            <span class="rt-chip">Creator profiles</span>
                            <span class="rt-chip">Campaign analytics</span>
                        </div>
                        <div class="rt-btn rt-btn-brand">
                            Join as Brand
                            <span class="rt-btn-arrow">→</span>
                        </div>
                    </div>
                </NuxtLink>
            </div>

            <p class="rt-footer-text">
                Already have an account?
                <NuxtLink to="/auth/login" class="rt-link">Log in</NuxtLink>
            </p>
        </div>
    </div>
</template>

<style scoped>
@import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

.register-type {
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

.rt-bg-grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(0, 0, 0, 0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.04) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}

.rt-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    pointer-events: none;
}

.rt-blob-1 {
    width: 400px;
    height: 400px;
    background: rgba(79, 70, 229, 0.08);
    top: -150px;
    right: -80px;
}

.rt-blob-2 {
    width: 350px;
    height: 350px;
    background: rgba(71, 85, 105, 0.08);
    bottom: -100px;
    left: -60px;
}

.rt-inner {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 720px;
    animation: rtFadeUp 0.6s ease both;
}

.rt-header {
    text-align: center;
    margin-bottom: 3rem;
}

.rt-logo {
    font-family: "Inter", sans-serif;
    font-weight: 800;
    font-size: 1.5rem;
    color: #1a1a18;
    text-decoration: none;
    letter-spacing: -0.02em;
}

.rt-sub {
    color: #888;
    font-size: 1rem;
    margin-top: 0.5rem;
    font-weight: 400;
}

.rt-cards {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
}

.rt-card {
    position: relative;
    border-radius: 24px;
    padding: 2.5rem 2rem;
    text-decoration: none;
    overflow: hidden;
    transition:
        transform 0.3s ease,
        box-shadow 0.3s ease;
    display: block;
}

.rt-card:hover {
    transform: translateY(-6px);
}

.rt-card-creator {
    background: linear-gradient(145deg, #eef2ff, #fff);
    border: 1px solid rgba(79, 70, 229, 0.12);
    box-shadow: 0 4px 24px rgba(79, 70, 229, 0.06);
}

.rt-card-creator:hover {
    box-shadow: 0 16px 48px rgba(79, 70, 229, 0.15);
}

.rt-card-brand {
    background: linear-gradient(145deg, #f1f5f9, #fff);
    border: 1px solid rgba(71, 85, 105, 0.15);
    box-shadow: 0 4px 24px rgba(71, 85, 105, 0.06);
}

.rt-card-brand:hover {
    box-shadow: 0 16px 48px rgba(71, 85, 105, 0.15);
}

.rt-card-bg {
    position: absolute;
    inset: 0;
    pointer-events: none;
}

.rt-card-creator .rt-card-bg {
    background: radial-gradient(
        ellipse at top right,
        rgba(79, 70, 229, 0.04),
        transparent 70%
    );
}

.rt-card-brand .rt-card-bg {
    background: radial-gradient(
        ellipse at top right,
        rgba(71, 85, 105, 0.06),
        transparent 70%
    );
}

.rt-card-content {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.rt-icon-wrap {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
}

.rt-icon-wrap :deep(.rt-icon) {
    width: 28px;
    height: 28px;
}

.rt-card-creator .rt-icon-wrap {
    background: rgba(79, 70, 229, 0.1);
    color: #4f46e5;
}

.rt-card-brand .rt-icon-wrap {
    background: rgba(71, 85, 105, 0.12);
    color: #475569;
}

.rt-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1a1a18;
    margin-bottom: 0.6rem;
    letter-spacing: -0.01em;
}

.rt-card-desc {
    font-size: 0.85rem;
    color: #777;
    line-height: 1.6;
    margin-bottom: 1.25rem;
    font-weight: 400;
}

.rt-chip-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    justify-content: center;
    margin-bottom: 1.75rem;
}

.rt-chip {
    font-size: 0.7rem;
    font-weight: 500;
    padding: 0.25rem 0.65rem;
    border-radius: 100px;
    background: rgba(0, 0, 0, 0.04);
    color: #666;
}

.rt-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.75rem 1.5rem;
    border-radius: 14px;
    font-size: 0.9rem;
    font-weight: 600;
    transition:
        transform 0.2s,
        box-shadow 0.2s;
}

.rt-btn-creator {
    background: #4f46e5;
    color: #fff;
    box-shadow: 0 4px 16px rgba(79, 70, 229, 0.3);
}

.rt-card:hover .rt-btn-creator {
    box-shadow: 0 8px 24px rgba(79, 70, 229, 0.45);
}

.rt-btn-brand {
    background: #0f172a;
    color: #fff;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
}

.rt-card:hover .rt-btn-brand {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.25);
}

.rt-btn-arrow {
    transition: transform 0.2s;
}

.rt-card:hover .rt-btn-arrow {
    transform: translateX(3px);
}

.rt-footer-text {
    text-align: center;
    margin-top: 2.5rem;
    font-size: 0.9rem;
    color: #888;
}

.rt-link {
    color: #4f46e5;
    font-weight: 600;
    text-decoration: none;
    transition: opacity 0.2s;
}

.rt-link:hover {
    opacity: 0.75;
}

@keyframes rtFadeUp {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@media (max-width: 640px) {
    .rt-cards {
        grid-template-columns: 1fr;
    }

    .rt-card {
        padding: 2rem 1.5rem;
    }

    .rt-header {
        margin-bottom: 2rem;
    }
}
</style>
