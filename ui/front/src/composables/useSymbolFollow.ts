import { onBeforeUnmount, onMounted, ref, watch } from "vue";
import type { Ref } from "vue";

import { pickActiveRef } from "contract/flatten";
import type { RefPosition } from "contract/flatten";

/**
 * Tracks which `[data-symbol-ref]` element inside `root` the reader is currently at. An IntersectionObserver
 * maintains the set of on-screen elements; a passive scroll listener re-picks among them as the reading line moves.
 * Call `refresh()` after the prose HTML changes.
 */
export function useSymbolFollow(root: Ref<HTMLElement | null>, refresh: Ref<unknown>) {
	const activeRef = ref<string | null>(null);
	const visible = new Map<Element, string>();
	let observer: IntersectionObserver | null = null;
	let frame: number | null = null;

	function pick(): void {
		frame = null;

		const positions: RefPosition[] = [];

		for (const [el, symbolRef] of visible) {
			const rect = el.getBoundingClientRect();

			positions.push({ ref: symbolRef, top: rect.top, bottom: rect.bottom });
		}

		activeRef.value = pickActiveRef(positions, window.innerHeight);
	}

	function schedule(): void {
		if (frame === null && typeof window !== "undefined") {
			frame = window.requestAnimationFrame(pick);
		}
	}

	function observe(): void {
		if (!observer || !root.value) {
			return;
		}

		observer.disconnect();
		visible.clear();

		for (const el of root.value.querySelectorAll<HTMLElement>("[data-symbol-ref]")) {
			observer.observe(el);
		}

		schedule();
	}

	onMounted(() => {
		if (typeof IntersectionObserver === "undefined") {
			return;
		}

		observer = new IntersectionObserver(
			(entries) => {
				for (const entry of entries) {
					const symbolRef = (entry.target as HTMLElement).dataset.symbolRef;

					if (!symbolRef) {
						continue;
					}

					if (entry.isIntersecting) {
						visible.set(entry.target, symbolRef);
					} else {
						visible.delete(entry.target);
					}
				}

				schedule();
			},
			{ threshold: [0, 1] },
		);

		window.addEventListener("scroll", schedule, { passive: true });
		window.addEventListener("resize", schedule);
		observe();
	});

	watch(refresh, () => {
		// The prose re-rendered; observe the new elements on the next frame.
		if (typeof window !== "undefined") {
			window.requestAnimationFrame(observe);
		}
	});

	onBeforeUnmount(() => {
		observer?.disconnect();
		window.removeEventListener("scroll", schedule);
		window.removeEventListener("resize", schedule);

		if (frame !== null) {
			window.cancelAnimationFrame(frame);
		}
	});

	return { activeRef, refresh: observe };
}
