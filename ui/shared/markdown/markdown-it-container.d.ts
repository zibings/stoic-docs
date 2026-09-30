// markdown-it-container ships no types that match markdown-it 15's own typings, so declare the small surface we use.
declare module "markdown-it-container" {
	import type { MarkdownIt, RendererRule } from "markdown-it";

	export interface ContainerOptions {
		marker?: string;
		validate?(params: string): boolean;
		render?: RendererRule;
	}

	const container: (md: MarkdownIt, name: string, options?: ContainerOptions) => void;

	export default container;
}
