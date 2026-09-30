import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import TryItCard from "components/learn/TryItCard.vue";

const step = { ordinal: 2, prompt: "Set staleTime to ten seconds.", hint: "It takes milliseconds.", answer: "{ staleTime: 10_000 }", expectedOutput: null };

describe("TryItCard", () => {
	it("reveals the hint and the answer, then emits done", async () => {
		const wrapper = mount(TryItCard, { props: { step, total: 3, done: false, language: "ts" } });

		expect(wrapper.text()).toContain("Try it · step 2 of 3");
		expect(wrapper.text()).not.toContain("It takes milliseconds.");

		await wrapper.find("button.tryit__btn--outline").trigger("click");
		expect(wrapper.text()).toContain("It takes milliseconds.");

		const answerButton = wrapper.findAll("button").find((b) => b.text() === "Show the answer")!;
		await answerButton.trigger("click");
		expect(wrapper.find(".tryit__answer").text()).toContain("staleTime");

		await wrapper.findAll("button").find((b) => b.text() === "Mark step done")!.trigger("click");
		expect(wrapper.emitted("done")).toHaveLength(1);
	});

	it("shows undo and the done tag once the step is done, and resets reveals on a new step", async () => {
		const wrapper = mount(TryItCard, { props: { step, total: 3, done: true, language: "ts" } });

		expect(wrapper.text()).toContain("done");
		expect(wrapper.findAll("button").some((b) => b.text() === "Undo")).toBe(true);

		await wrapper.find("button.tryit__btn--outline").trigger("click");
		expect(wrapper.text()).toContain("It takes milliseconds.");

		await wrapper.setProps({ step: { ...step, ordinal: 3 } });
		expect(wrapper.text()).not.toContain("It takes milliseconds.");
	});
});
