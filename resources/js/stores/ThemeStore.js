import { defineStore } from 'pinia'
import { ref } from 'vue'

const LIGHT = 'light'
const DARK = 'dark'
const KEY = 'theme'

export default defineStore(KEY, () => {
    const theme = ref(localStorage.getItem(KEY) || LIGHT)

    function toggleTheme() {
        if (theme.value == LIGHT) {
            theme.value = DARK
            localStorage.setItem(KEY, DARK)
        } else {
            theme.value = LIGHT
            localStorage.setItem(KEY, LIGHT)
        }

        useEffect()
    }

    function isDark() {
        return theme.value == DARK
    }

    function useEffect() {
        const root = window.document.documentElement;

        root.classList.remove("light", "dark");

        if (theme === "system") {
            const systemTheme = window.matchMedia("(prefers-color-scheme: dark)")
                .matches
                ? "dark"
                : "light";

            root.classList.add(systemTheme);
            return;
        }

        root.classList.add(theme.value);
    }

    return {
        toggleTheme,
        isDark,
        useEffect
    }
})
