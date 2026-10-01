import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import type { Ref } from "vue";

/**
 * Tracks which heading (by id, among `ids`, inside `root`) the reader is at: the last one whose top has passed the
 * reading line just under the sticky header, or the first one before any has. Client only; the pre-rendered table
 * of contents simply has no active entry.
 */
export function useHeadingFollow(root: Ref<HTMLElement | null>, ids: Ref<string[]>) {
	const activeId = ref<string | null>(null);
	let frame: number | null = null;

	function readingLine(): number {
		const header = document.querySelector<HTMLElement>(".app-header");

		return (header?.getBoundingClientRect().height ?? 0) + 24;
	}

	function pick(): void {
		frame = null;

		const container = root.value;

		if (!container || ids.value.length === 0) {
			activeId.value = null;

			return;
		}

		const line = readingLine();
		let current: string | null = null;

		for (const id of ids.value) {
			const el = container.querySelector<HTMLElement>(`#${CSS.escape(id)}`);

			if (!el) {
				continue;
			}

			if (el.getBoundingClientRect().top <= line) {
				current = id;
			} else {
				break;
			}
		}

		activeId.value = current ?? ids.value[0] ?? null;
	}

	function schedule(): void {
		if (frame === null && typeof window !== "undefined") {
			frame = window.requestAnimationFrame(pick);
		}
	}

	onMounted(() => {
		window.addEventListener("scroll", schedule, { passive: true });
		window.addEventListener("resize", schedule);
		schedule();
	});

	watch(ids, () => {
		// The page changed; the new headings are in the DOM on the next frame.
		if (typeof window !== "undefined") {
			window.requestAnimationFrame(schedule);
		}
	});

	onBeforeUnmount(() => {
		window.removeEventListener("scroll", schedule);
		window.removeEventListener("resize", schedule);

		if (frame !== null) {
			window.cancelAnimationFrame(frame);
		}
	});

	return { activeId, refresh: schedule };
}
