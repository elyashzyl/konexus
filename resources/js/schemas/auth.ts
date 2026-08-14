import { z } from 'zod';

export const loginSchema = z.object({
    email: z.string().trim().min(1, 'Email is required.').email('Enter a valid email address.'),
    password: z.string().min(1, 'Password is required.'),
    remember: z.boolean().optional().default(false),
});

export type LoginFormValues = z.infer<typeof loginSchema>;

export const registerSchema = z
    .object({
        school_name: z.string().trim().min(2, 'School name is required.').max(255, 'School name is too long.'),
        short_name: z.string().trim().max(80, 'Short name is too long.').optional(),
        school_id: z.string().trim().max(80, 'School ID is too long.').optional(),
        region: z.string().trim().max(120, 'Region is too long.').optional(),
        division: z.string().trim().max(120, 'Division is too long.').optional(),
        district: z.string().trim().max(120, 'District is too long.').optional(),
        address: z.string().trim().max(500, 'Address is too long.').optional(),
        contact_number: z.string().trim().max(60, 'Contact number is too long.').optional(),
        school_email: z.string().trim().email('Enter a valid school email address.').optional().or(z.literal('')),
        website: z.string().trim().url('Enter a valid website URL.').optional().or(z.literal('')),
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
