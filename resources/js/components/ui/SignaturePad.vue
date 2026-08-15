<script setup lang="ts">
import { Eraser } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    modelValue?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const canvasRef = ref<HTMLCanvasElement | null>(null);
const drawing = ref(false);
let lastX = 0;
let lastY = 0;

const resizeCanvas = () => {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const rect = canvas.getBoundingClientRect();
    const dpr = window.devicePixelRatio || 1;
    canvas.width = Math.max(1, Math.round(rect.width * dpr));
    canvas.height = Math.max(1, Math.round(rect.height * dpr));

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return;
    }

    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.strokeStyle = 'hsl(222 47% 11%)';

    if (props.modelValue) {
        restore(props.modelValue);
    }
};

const restore = (dataUrl: string) => {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return;
    }

    const img = new Image();

    img.onload = () => {
        ctx.drawImage(img, 0, 0, canvas.clientWidth, canvas.clientHeight);
    };
    img.src = dataUrl;
};

const getPos = (event: PointerEvent): { x: number; y: number } => {
    const rect = canvasRef.value!.getBoundingClientRect();

    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
};

const start = (event: PointerEvent) => {
    if (!canvasRef.value) {
        return;
    }

    drawing.value = true;
    canvasRef.value.setPointerCapture(event.pointerId);

    const pos = getPos(event);
    lastX = pos.x;
    lastY = pos.y;

    const ctx = canvasRef.value.getContext('2d')!;
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(lastX + 0.01, lastY + 0.01);
    ctx.stroke();
};

const move = (event: PointerEvent) => {
    if (!drawing.value || !canvasRef.value) {
        return;
    }

    const pos = getPos(event);
    const ctx = canvasRef.value.getContext('2d')!;
    ctx.beginPath();
    ctx.moveTo(lastX, lastY);
    ctx.lineTo(pos.x, pos.y);
    ctx.stroke();
    lastX = pos.x;
    lastY = pos.y;
};

const end = () => {
    if (!drawing.value || !canvasRef.value) {
        return;
    }

    drawing.value = false;
    emit('update:modelValue', canvasRef.value.toDataURL('image/png'));
};

const clear = () => {
    const canvas = canvasRef.value;

    if (!canvas) {
        return;
    }

    const ctx = canvas.getContext('2d')!;
    ctx.save();
    ctx.setTransform(1, 0, 0, 1, 0, 0);
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    ctx.restore();
    emit('update:modelValue', '');
};

onMounted(() => {
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);
});

onUnmounted(() => {
    window.removeEventListener('resize', resizeCanvas);
});
</script>

<template>
    <div class="grid gap-2">
        <div
            class="relative overflow-hidden rounded-lg border border-border/70 bg-white"
            :class="modelValue ? 'border-primary/40' : 'border-border/70'"
        >
            <canvas
                ref="canvasRef"
                class="h-44 w-full cursor-crosshair touch-none select-none"
                @pointerdown="start"
                @pointermove="move"
                @pointerup="end"
                @pointercancel="end"
            />
            <span v-if="!modelValue" class="pointer-events-none absolute inset-0 flex items-end justify-center pb-3 text-xs text-muted-foreground/60">
                Sign inside the box using your mouse, stylus, or finger
            </span>
        </div>
        <button
            type="button"
            class="flex items-center gap-1.5 justify-self-end text-xs font-medium text-muted-foreground hover:text-destructive"
            @click="clear"
        >
            <Eraser class="size-3.5" />
            Clear signature
        </button>
    </div>
</template>