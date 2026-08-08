import { z } from 'zod';

export const loginSchema = z.object({
    email: z.string().trim().min(1, 'Email is required.').email('Enter a valid email address.'),
    password: z.string().min(1, 'Password is required.'),
    remember: z.boolean().optional().default(false),
});

export type LoginFormValues = z.infer<typeof loginSchema>;

export const registerSchema = z
    .object({
        name: z.string().trim().min(2, 'Name must be at least 2 characters.').max(255, 'Name is too long.'),
        email: z.string().trim().min(1, 'Email is required.').email('Enter a valid email address.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Please confirm your password.'),
    })
    .refine((values) => values.password === values.password_confirmation, {
        message: 'Passwords do not match.',
        path: ['password_confirmation'],
    });

export type RegisterFormValues = z.infer<typeof registerSchema>;

export const forgotPasswordSchema = z.object({
    email: z.string().trim().min(1, 'Email is required.').email('Enter a valid email address.'),
});

export type ForgotPasswordFormValues = z.infer<typeof forgotPasswordSchema>;

export const resetPasswordSchema = z
    .object({
        email: z.string().trim().min(1, 'Email is required.').email('Enter a valid email address.'),
        password: z.string().min(8, 'Password must be at least 8 characters.'),
        password_confirmation: z.string().min(1, 'Please confirm your password.'),
        token: z.string().min(1, 'Reset token is missing.'),
    })
    .refine((values) => values.password === values.password_confirmation, {
        message: 'Passwords do not match.',
        path: ['password_confirmation'],
    });

export type ResetPasswordFormValues = z.infer<typeof resetPasswordSchema>;
