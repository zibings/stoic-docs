import { createPinia, setActivePinia } from "pinia";
import { beforeEach, describe, expect, it } from "vitest";

import { REVIEW_KEY_PREFIX, useReviewStore } from "stores/review";

describe("review store", () => {
	beforeEach(() => {
		setActivePinia(createPinia());
		window.localStorage.clear();
	});

	it("keeps marks per range and persists them", () => {
		const review = useReviewStore();

		review.load("v3.8", "v4.2");
		review.mark(1);
		review.toggle(2);
		review.toggle(1);

		expect(review.reviewed).toEqual([2]);
		expect(review.isReviewed(2)).toBe(true);
		expect(JSON.parse(window.localStorage.getItem(`${REVIEW_KEY_PREFIX}v3.8->v4.2`) ?? "[]")).toEqual([2]);

		review.load("v4.0", "v4.2");
		expect(review.reviewed).toEqual([]);

		review.load("v3.8", "v4.2");
		expect(review.reviewed).toEqual([2]);
	});

	it("restores marks from storage in a fresh store", () => {
		window.localStorage.setItem(`${REVIEW_KEY_PREFIX}v3.8->v4.2`, JSON.stringify([5, "bad", 7]));

		const review = useReviewStore();

		review.load("v3.8", "v4.2");

		expect(review.reviewed).toEqual([5, 7]);
		expect(review.count).toBe(2);
	});
});
