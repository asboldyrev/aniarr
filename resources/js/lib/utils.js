import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Утилита для объединения классов CSS
 * Аналог функции cn из shadcn/ui
 * Использует clsx для объединения классов и tailwind-merge для разрешения конфликтов Tailwind
 */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
