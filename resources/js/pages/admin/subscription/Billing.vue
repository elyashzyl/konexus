<script setup lang="ts">
import AdminPageHeader from '@/components/AdminPageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { extractError, extractFieldErrors } from '@/lib/api';
import { subscriptionApi, type InvoiceItem, type PaymentItem, type SubscriptionItem } from '@/lib/subscriptionApi';
import { Banknote, FilePlus2 } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import { toast } from 'vue-sonner';

const loading = ref(true);
const saving = ref(false);
const fieldErrors = ref<Record<string, string[]>>({});
const tab = ref<'invoices' | 'payments'>('invoices');

const invoices = ref<InvoiceItem[]>([]);
const payments = ref<PaymentItem[]>([]);
const invoiceTotal = ref(0);
const invoicePage = ref(1);
const paymentTotal = ref(0);
const paymentPage = ref(1);
const perPage = 15;
const statusFilter = ref<'all' | string>('all');

const subscriptions = ref<SubscriptionItem[]>([]);

const generateOpen = ref(false);
const generateForm = ref<{ subscription_id: string; amount: string; discount: string; tax_rate: string; due_date: string; notes: string }>({
    subscription_id: '',
    amount: '',
    discount: '0',
    tax_rate: '0',
    due_date: '',
    notes: '',
});

const markPaidTarget = ref<InvoiceItem | null>(null);
const markPaidOpen = ref(false);
const markPaidForm = ref<{ payment_reference: string; payment_method: string }>({ payment_reference: '', payment_method: 'manual' });

const paymentTarget = ref<InvoiceItem | null>(null);
const paymentOpen = ref(false);
const paymentForm = ref<{ amount: string; payment_method: string; reference_number: string; payment_date: string; notes: string }>({
    amount: '',
    payment_method: 'manual',
    reference_number: '',
    payment_date: '',
    notes: '',
});

onMounted(refresh);

async function refresh(): Promise<void> {
    loading.value = true;
    try {
        const [invoiceData, paymentData] = await Promise.all([
            subscriptionApi.billing.invoices({
                page: invoicePage.value,
                per_page: perPage,
                status: statusFilter.value === 'all' ? undefined : statusFilter.value,
            }),
            subscriptionApi.billing.payments({ page: paymentPage.value, per_page: perPage }),
        ]);
        invoices.value = invoiceData.items;
        invoiceTotal.value = invoiceData.pagination.total;
        payments.value = paymentData.items;
        paymentTotal.value = paymentData.pagination.total;
    } catch (error) {
        toast.error(extractError(error));
    } finally {
        loading.value = false;
    }
}

async function openGenerate(): Promise<void> {
    generateForm.value = { subscription_id: '', amount: '', discount: '0', tax_rate: '0', due_date: '', notes: '' };
    fieldErrors.value = {};
    try {
        const data = await subscriptionApi.subscriptions.index({ per_page: 100 });
        subscriptions.value = data.items;
    } catch (error) {
        toast.error(extractError(error));
    }
    generateOpen.value = true;
}

async function generate(): Promise<void> {
    saving.value = true;
    fieldErrors.value = {};
    try {
        await subscriptionApi.billing.generate({
            subscription_id: Number(generateForm.value.subscription_id),
            amount: generateForm.value.amount ? Number(generateForm.value.amount) : undefined,
            discount: generateForm.value.discount ? Number(generateForm.value.discount) : undefined,
            tax_rate: generateForm.value.tax_rate ? Number(generateForm.value.tax_rate) : undefined,
            due_date: generateForm.value.due_date || undefined,
            notes: generateForm.value.notes || undefined,
        });
        toast.success('Invoice generated.');
        generateOpen.value = false;
        await refresh();
    } catch (error) {
        fieldErrors.value = extractFieldErrors(error);
        toast.error(extractError(error));
    } finally {
        saving.value = false;
    }
}

function openMarkPaid(invoice: InvoiceItem): void {
    markPaidTarget.value = invoice;
    markPaidOpen.value = true;
    markPaidForm.value = { payment_reference: '', payment_method: 'manual' };
}

function openRecordPayment(invoice: InvoiceItem): void {
    paymentTarget.value = invoice;
    paymentOpen.value = true;
    paymentForm.value = {
        amount: String(invoice.balance ?? invoice.total),
        payment_method: 'manual',
        reference_number: '',
        payment_date: '',
        notes: '',
    };
}

async function markPaid(): Promise<void> {
    if (!markPaidTarget.value) return;
    try {
        await subscriptionApi.billing.markPaid(markPaidTarget.value.id, {
            payment_reference: markPaidForm.value.payment_reference || undefined,
            payment_method: markPaidForm.value.payment_method,
        });
        toast.success('Invoice marked as paid.');
        markPaidOpen.value = false;
        markPaidTarget.value = null;
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

async function recordPayment(): Promise<void> {
    if (!paymentTarget.value) return;
    try {
        await subscriptionApi.billing.recordPayment({
            invoice_id: paymentTarget.value.id,
            amount: Number(paymentForm.value.amount),
            payment_method: paymentForm.value.payment_method,
            reference_number: paymentForm.value.reference_number || undefined,
            payment_date: paymentForm.value.payment_date || undefined,
            notes: paymentForm.value.notes || undefined,
        });
        toast.success('Payment recorded.');
        paymentOpen.value = false;
        paymentTarget.value = null;
        await refresh();
    } catch (error) {
        toast.error(extractError(error));
    }
}

const money = (v: number | undefined) => `₱${Number(v ?? 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
</script>

<template>
    <div class="relative">
        <div
            class="pointer-events-none absolute inset-x-0 top-0 h-80 bg-[radial-gradient(45rem_22rem_at_50%_-28%,hsl(26_57%_40%/0.08),transparent)]"
        />

        <div class="relative w-full px-5 pb-20 pt-10 sm:px-8 lg:px-12">
            <AdminPageHeader
                :icon="Banknote"
                index="05"
                eyebrow="Platform"
                title="Billing"
                description="Invoices and recorded payments across tenants."
            >
                <template #actions>
                    <Button @click="openGenerate"><FilePlus2 class="size-4" /> Generate invoice</Button>
                </template>
            </AdminPageHeader>

            <div class="portal-rise mt-10 flex items-center gap-2">
                <div class="inline-flex items-center gap-1 rounded-full border p-1">
                    <Button variant="ghost" size="sm" :class="tab === 'invoices' ? 'bg-secondary' : ''" @click="tab = 'invoices'">Invoices</Button>
                    <Button variant="ghost" size="sm" :class="tab === 'payments' ? 'bg-secondary' : ''" @click="tab = 'payments'">Payments</Button>
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <Select
                        v-if="tab === 'invoices'"
                        :model-value="statusFilter"
                        @update:model-value="
                            (v: string) => {
                                statusFilter = v;
                                refresh();
                            }
                        "
                    >
                        <SelectTrigger class="w-44"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="overdue">Overdue</SelectItem>
                            <SelectItem value="partially_paid">Partially paid</SelectItem>
                        </SelectContent>
                    </Select>
                    <span class="text-sm text-muted-foreground">{{ tab === 'invoices' ? invoiceTotal : paymentTotal }} records</span>
                </div>
            </div>

            <section class="portal-rise mt-6" style="animation-delay: 120ms">
                <Card class="relative overflow-hidden border-border/60 bg-card/60">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-primary/25 to-transparent" />
                    <CardHeader>
                        <CardTitle class="font-display text-lg font-medium tracking-[-0.01em]">{{
                            tab === 'invoices' ? 'Invoices' : 'Payments'
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="loading" class="space-y-2">
                            <Skeleton v-for="i in 6" :key="i" class="h-12" />
                        </div>

                        <Table v-else-if="tab === 'invoices'">
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Tenant</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Due</TableHead>
                                    <TableHead>Total</TableHead>
                                    <TableHead>Balance</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="invoice in invoices" :key="invoice.id">
                                    <TableCell class="font-mono text-xs">{{ invoice.invoice_number }}</TableCell>
                                    <TableCell class="text-sm">{{ invoice.tenant?.name ?? `Tenant #${invoice.tenant_id}` }}</TableCell>
                                    <TableCell
                                        ><Badge variant="secondary">{{ invoice.status }}</Badge></TableCell
                                    >
                                    <TableCell class="text-xs">{{ invoice.due_date ?? '—' }}</TableCell>
                                    <TableCell>{{ money(invoice.total) }}</TableCell>
                                    <TableCell :class="(invoice.balance ?? 0) > 0 ? 'text-destructive' : ''">{{
                                        money(invoice.balance ?? 0)
                                    }}</TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-1">
                                            <Button v-if="invoice.status !== 'paid'" variant="ghost" size="sm" @click="openMarkPaid(invoice)">
                                                Mark paid
                                            </Button>
                                            <Button v-if="(invoice.balance ?? 0) > 0" variant="ghost" size="sm" @click="openRecordPayment(invoice)">
                                                Record payment
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <Table v-else>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Reference</TableHead>
                                    <TableHead>Invoice</TableHead>
                                    <TableHead>Tenant</TableHead>
                                    <TableHead>Method</TableHead>
                                    <TableHead>Date</TableHead>
                                    <TableHead class="text-right">Amount</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="payment in payments" :key="payment.id">
                                    <TableCell class="font-mono text-xs">{{ payment.reference_number ?? `P-${payment.id}` }}</TableCell>
                                    <TableCell class="font-mono text-xs">{{ payment.invoice?.invoice_number ?? '—' }}</TableCell>
                                    <TableCell class="text-sm">{{ payment.tenant?.name ?? `Tenant #${payment.tenant_id}` }}</TableCell>
                                    <TableCell class="text-xs capitalize">{{ payment.payment_method }}</TableCell>
                                    <TableCell class="text-xs">{{
                                        payment.payment_date ?? new Date(payment.created_at).toLocaleDateString()
                                    }}</TableCell>
                                    <TableCell class="text-right">{{ money(payment.amount) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div v-if="tab === 'invoices' && invoiceTotal > perPage" class="mt-4 flex items-center justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="invoicePage <= 1"
                                @click="
                                    invoicePage--;
                                    refresh();
                                "
                                >Previous</Button
                            >
                            <span class="text-sm text-muted-foreground">Page {{ invoicePage }}</span>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="invoicePage * perPage >= invoiceTotal"
                                @click="
                                    invoicePage++;
                                    refresh();
                                "
                                >Next</Button
                            >
                        </div>
                        <div v-else-if="paymentTotal > perPage" class="mt-4 flex items-center justify-end gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="paymentPage <= 1"
                                @click="
                                    paymentPage--;
                                    refresh();
                                "
                                >Previous</Button
                            >
                            <span class="text-sm text-muted-foreground">Page {{ paymentPage }}</span>
                            <Button
                                variant="outline"
                                size="sm"
                                :disabled="paymentPage * perPage >= paymentTotal"
                                @click="
                                    paymentPage++;
                                    refresh();
                                "
                                >Next</Button
                            >
                        </div>
                    </CardContent>
                </Card>
            </section>

            <Dialog v-model:open="generateOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Generate invoice</DialogTitle>
                        <DialogDescription>Bill a subscription for a period or an ad-hoc amount.</DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="generate">
                        <div class="space-y-2">
                            <Label for="gen-sub">Subscription</Label>
                            <Select v-model="generateForm.subscription_id">
                                <SelectTrigger id="gen-sub" class="w-full"><SelectValue placeholder="Select subscription" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="sub in subscriptions" :key="sub.id" :value="String(sub.id)">
                                        {{ sub.subscription_code }} · {{ sub.tenant?.name }} · {{ money(sub.amount) }}/{{ sub.billing_cycle }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="fieldErrors.subscription_id" class="text-xs text-destructive">{{ fieldErrors.subscription_id[0] }}</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="gen-amount">Amount (leave blank for plan price)</Label>
                                <Input id="gen-amount" type="number" step="0.01" v-model="generateForm.amount" placeholder="0.00" />
                            </div>
                            <div class="space-y-2">
                                <Label for="gen-due">Due date</Label>
                                <Input id="gen-due" type="date" v-model="generateForm.due_date" />
                            </div>
                            <div class="space-y-2">
                                <Label for="gen-discount">Discount (₱)</Label>
                                <Input id="gen-discount" type="number" step="0.01" v-model="generateForm.discount" />
                            </div>
                            <div class="space-y-2">
                                <Label for="gen-tax">Tax rate (%)</Label>
                                <Input id="gen-tax" type="number" step="0.01" v-model="generateForm.tax_rate" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="gen-notes">Notes</Label>
                            <Textarea id="gen-notes" v-model="generateForm.notes" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="generateOpen = false">Cancel</Button>
                            <Button type="submit" :disabled="saving">{{ saving ? 'Generating…' : 'Generate' }}</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="markPaidOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Mark invoice as paid</DialogTitle>
                        <DialogDescription>{{ markPaidTarget?.invoice_number }} · {{ money(markPaidTarget?.total) }}</DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="markPaid">
                        <div class="space-y-2">
                            <Label for="mp-method">Payment method</Label>
                            <Select v-model="markPaidForm.payment_method">
                                <SelectTrigger id="mp-method" class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="manual">Manual</SelectItem>
                                    <SelectItem value="bank_transfer">Bank transfer</SelectItem>
                                    <SelectItem value="gcash">GCash</SelectItem>
                                    <SelectItem value="card">Card</SelectItem>
                                    <SelectItem value="cash">Cash</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="mp-ref">Reference</Label>
                            <Input id="mp-ref" v-model="markPaidForm.payment_reference" placeholder="e.g. receipt number" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="markPaidOpen = false">Cancel</Button>
                            <Button type="submit">Mark paid</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>

            <Dialog v-model:open="paymentOpen">
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Record payment</DialogTitle>
                        <DialogDescription>{{ paymentTarget?.invoice_number }} · balance {{ money(paymentTarget?.balance) }}</DialogDescription>
                    </DialogHeader>
                    <form class="space-y-4" @submit.prevent="recordPayment">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="pay-amount">Amount</Label>
                                <Input id="pay-amount" type="number" step="0.01" v-model="paymentForm.amount" />
                            </div>
                            <div class="space-y-2">
                                <Label for="pay-date">Date</Label>
                                <Input id="pay-date" type="date" v-model="paymentForm.payment_date" />
                            </div>
                        </div>
                        <div class="space-y-2">
                            <Label for="pay-method">Method</Label>
                            <Select v-model="paymentForm.payment_method">
                                <SelectTrigger id="pay-method" class="w-full"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="manual">Manual</SelectItem>
                                    <SelectItem value="bank_transfer">Bank transfer</SelectItem>
                                    <SelectItem value="gcash">GCash</SelectItem>
                                    <SelectItem value="card">Card</SelectItem>
                                    <SelectItem value="cash">Cash</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="space-y-2">
                            <Label for="pay-ref">Reference</Label>
                            <Input id="pay-ref" v-model="paymentForm.reference_number" />
                        </div>
                        <div class="space-y-2">
                            <Label for="pay-notes">Notes</Label>
                            <Textarea id="pay-notes" v-model="paymentForm.notes" />
                        </div>
                        <DialogFooter>
                            <Button type="button" variant="outline" @click="paymentOpen = false">Cancel</Button>
                            <Button type="submit">Record</Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
