<?php

	namespace Api1_1;

	use OpenApi\Annotations as OA;

	use Stoic\Web\Api\Response;
	use Stoic\Web\Request;
	use Stoic\Web\Resources\HttpStatusCodes;

	use Zibings\ApiController;
	use Zibings\DocsReader;
	use Zibings\DocVersion;

	/**
	 * @OA\Schema(
	 *   schema="DocVersion",
	 *   type="object",
	 *   @OA\Property(property="id",          type="integer"),
	 *   @OA\Property(property="tag",         type="string",  example="4.2.0"),
	 *   @OA\Property(property="label",       type="string",  example="v4.2"),
	 *   @OA\Property(property="sortKey",     type="integer"),
	 *   @OA\Property(property="releasedAt",  type="string",  nullable=true, example="2025-09-09"),
	 *   @OA\Property(property="isLatest",    type="boolean"),
	 *   @OA\Property(property="isSupported", type="boolean")
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocContextOption",
	 *   type="object",
	 *   @OA\Property(property="key",       type="string", example="ts"),
	 *   @OA\Property(property="label",     type="string", example="TypeScript"),
	 *   @OA\Property(property="isDefault", type="boolean")
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocParam",
	 *   type="object",
	 *   description="One parameter or field of a contract",
	 *   @OA\Property(property="name",        type="string"),
	 *   @OA\Property(property="type",        type="string"),
	 *   @OA\Property(property="required",    type="boolean"),
	 *   @OA\Property(property="default",     type="string", nullable=true),
	 *   @OA\Property(property="description", type="string"),
	 *   @OA\Property(property="since",       type="string", nullable=true)
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocContract",
	 *   type="object",
	 *   description="A symbol's contract over a version range",
	 *   @OA\Property(property="id",                  type="integer"),
	 *   @OA\Property(property="symbolId",            type="integer"),
	 *   @OA\Property(property="introducedVersionId", type="integer"),
	 *   @OA\Property(property="removedVersionId",    type="integer", nullable=true),
	 *   @OA\Property(property="introducedLabel",     type="string"),
	 *   @OA\Property(property="removedLabel",        type="string",  nullable=true),
	 *   @OA\Property(property="signature",           type="string"),
	 *   @OA\Property(property="summary",             type="string"),
	 *   @OA\Property(property="status",              type="string",  example="stable"),
	 *   @OA\Property(property="params",              type="array",   @OA\Items(ref="#/components/schemas/DocParam")),
	 *   @OA\Property(property="returns",             type="object"),
	 *   @OA\Property(property="throws",              type="array",   @OA\Items(type="object")),
	 *   @OA\Property(property="sourcePath",          type="string",  nullable=true),
	 *   @OA\Property(property="sourceLine",          type="integer", nullable=true),
	 *   @OA\Property(property="sourceUrl",           type="string",  nullable=true)
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocSymbolNode",
	 *   type="object",
	 *   description="A symbol resolved at a version, with the contract valid there",
	 *   @OA\Property(property="ref",          type="string", example="tessel/query#QueryOptions.staleTime"),
	 *   @OA\Property(property="name",         type="string", example="QueryOptions.staleTime"),
	 *   @OA\Property(property="shortName",    type="string", example="staleTime"),
	 *   @OA\Property(property="kind",         type="string", example="option"),
	 *   @OA\Property(property="state",        type="string", enum={"current", "removed"}),
	 *   @OA\Property(property="sinceLabel",   type="string"),
	 *   @OA\Property(property="changedLabel", type="string", nullable=true),
	 *   @OA\Property(property="removedLabel", type="string", nullable=true),
	 *   @OA\Property(property="route",        type="string", example="/v4.2/reference/tessel/query/QueryOptions.staleTime"),
	 *   @OA\Property(property="contract",     ref="#/components/schemas/DocContract"),
	 *   @OA\Property(property="children",     type="array", @OA\Items(type="object"))
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocPageSummary",
	 *   type="object",
	 *   @OA\Property(property="id",      type="integer"),
	 *   @OA\Property(property="mode",    type="string", enum={"learn", "do", "reference", "explain"}),
	 *   @OA\Property(property="slug",    type="string"),
	 *   @OA\Property(property="title",   type="string"),
	 *   @OA\Property(property="summary", type="string"),
	 *   @OA\Property(property="minutes", type="integer", nullable=true),
	 *   @OA\Property(property="route",   type="string")
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocPage",
	 *   allOf={
	 *     @OA\Schema(ref="#/components/schemas/DocPageSummary"),
	 *     @OA\Schema(
	 *       type="object",
	 *       @OA\Property(property="body",            type="string", description="Markdown with directives, unrendered"),
	 *       @OA\Property(property="introducedLabel", type="string"),
	 *       @OA\Property(property="removedLabel",    type="string", nullable=true),
	 *       @OA\Property(property="updated",         type="string")
	 *     )
	 *   }
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocCodeSample",
	 *   type="object",
	 *   @OA\Property(property="key",               type="string"),
	 *   @OA\Property(property="title",             type="string", nullable=true, description="Display title, usually a file name"),
	 *   @OA\Property(property="language",          type="string"),
	 *   @OA\Property(property="variant",           type="string", nullable=true),
	 *   @OA\Property(property="code",              type="string"),
	 *   @OA\Property(property="isTested",          type="boolean"),
	 *   @OA\Property(property="lastTestPassLabel", type="string", nullable=true)
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocChangeSummary",
	 *   type="object",
	 *   @OA\Property(property="id",           type="integer"),
	 *   @OA\Property(property="kind",         type="string", enum={"breaking", "behavior", "deprecated", "added", "removed"}),
	 *   @OA\Property(property="title",        type="string"),
	 *   @OA\Property(property="versionLabel", type="string"),
	 *   @OA\Property(property="hasCodemod",   type="boolean"),
	 *   @OA\Property(property="codemodCmd",   type="string", nullable=true)
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocContractDiff",
	 *   type="object",
	 *   @OA\Property(property="ref",              type="string"),
	 *   @OA\Property(property="name",             type="string"),
	 *   @OA\Property(property="before",           ref="#/components/schemas/DocContract", nullable=true),
	 *   @OA\Property(property="after",            ref="#/components/schemas/DocContract", nullable=true),
	 *   @OA\Property(property="signatureChanged", type="boolean"),
	 *   @OA\Property(property="paramsAdded",      type="array", @OA\Items(ref="#/components/schemas/DocParam")),
	 *   @OA\Property(property="paramsChanged",    type="array", @OA\Items(type="object")),
	 *   @OA\Property(property="paramsRemoved",    type="array", @OA\Items(ref="#/components/schemas/DocParam"))
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocError",
	 *   type="string",
	 *   description="Error responses carry a plain message string",
	 *   example="Unknown version 'v9.9'"
	 * )
	 */
	class DocsSchemas { }

	/**
	 * Public read API for the documentation site. Every endpoint is anonymous; version segments accept a label
	 * (`v4.2`), a tag (`4.2.0`), or `latest`.
	 *
	 * @OA\Tag(
	 *   name="Docs",
	 *   description="Public documentation read endpoints"
	 * )
	 *
	 * @package Zibings\Api1_1
	 */
	class Docs extends ApiController {
		/**
		 * Registers the controller endpoints. Specific patterns come first because endpoints are matched in order.
		 *
		 * @return void
		 */
		protected function registerEndpoints() : void {
			$this->registerEndpoint('GET', '/^\/?Docs\/Site\/?$/i',                                  'site',     false);
			$this->registerEndpoint('GET', '/^\/?Docs\/Versions\/?$/i',                              'versions', false);
			$this->registerEndpoint('GET', '/^\/?Docs\/Tree\/([^\/]+)\/?$/i',                        'tree',     false);
			$this->registerEndpoint('GET', '/^\/?Docs\/Symbol\/([^\/]+)\/(.+)\/([^\/]+)\/?$/i',       'symbol',   false);
			$this->registerEndpoint('GET', '/^\/?Docs\/Page\/([^\/]+)\/([a-z]+)\/(.+?)\/?$/i',        'page',     false);
			$this->registerEndpoint('GET', '/^\/?Docs\/Diff\/([^\/]+)\/([^\/]+)\/?$/i',               'diff',     false);
			$this->registerEndpoint('GET', '/^\/?Docs\/Manifest\/([^\/]+)\/?$/i',                    'manifest', false);

			return;
		}

		/**
		 * Site bootstrap: versions, context options, and library identity.
		 *
		 * @OA\Get(
		 *   path="/Docs/Site",
		 *   operationId="docsSite",
		 *   summary="Site bootstrap",
		 *   description="Everything the shell needs on first load: versions, the latest label, context-bar options, and the library's name and repository",
		 *   tags={"Docs"},
		 *   @OA\Response(
		 *     response="200",
		 *     description="OK",
		 *     @OA\JsonContent(
		 *       type="object",
		 *       @OA\Property(property="libraryName", type="string"),
		 *       @OA\Property(property="repoUrl",     type="string"),
		 *       @OA\Property(property="siteUrl",     type="string", nullable=true, description="Public origin of the docs site, used for the sitemap; null until configured"),
		 *       @OA\Property(property="latest",      type="string", nullable=true, example="v4.2"),
		 *       @OA\Property(property="versions",    type="array", @OA\Items(ref="#/components/schemas/DocVersion")),
		 *       @OA\Property(
		 *         property="contextOptions",
		 *         type="object",
		 *         @OA\Property(property="languages",       type="array", @OA\Items(ref="#/components/schemas/DocContextOption")),
		 *         @OA\Property(property="packageManagers", type="array", @OA\Items(ref="#/components/schemas/DocContextOption"))
		 *       ),
		 *       @OA\Property(property="modes", type="array", @OA\Items(type="string"))
		 *     )
		 *   )
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function site(Request $request, null|array $matches = null) : Response {
			$ret = $this->newResponse();
			$ret->setData($this->reader()->site());

			return $ret;
		}

		/**
		 * All versions, oldest first.
		 *
		 * @OA\Get(
		 *   path="/Docs/Versions",
		 *   operationId="docsVersions",
		 *   summary="List versions",
		 *   description="Every documented version, oldest first",
		 *   tags={"Docs"},
		 *   @OA\Response(
		 *     response="200",
		 *     description="OK",
		 *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocVersion"))
		 *   )
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function versions(Request $request, null|array $matches = null) : Response {
			$ret = $this->newResponse();
			$ret->setData($this->reader()->versionList());

			return $ret;
		}

		/**
		 * The browse tree at a version.
		 *
		 * @OA\Get(
		 *   path="/Docs/Tree/{version}",
		 *   operationId="docsTree",
		 *   summary="Browse tree at a version",
		 *   description="Modules with their symbols resolved at the version; removed symbols are included unless removed=0",
		 *   tags={"Docs"},
		 *   @OA\Parameter(name="version", in="path", required=true, description="Version label, tag, or `latest`", @OA\Schema(type="string", example="v4.2")),
		 *   @OA\Parameter(name="removed", in="query", required=false, description="Set to 0 to omit removed symbols", @OA\Schema(type="integer", enum={0, 1})),
		 *   @OA\Response(
		 *     response="200",
		 *     description="OK",
		 *     @OA\JsonContent(
		 *       type="object",
		 *       @OA\Property(property="version", ref="#/components/schemas/DocVersion"),
		 *       @OA\Property(
		 *         property="modules",
		 *         type="array",
		 *         @OA\Items(
		 *           type="object",
		 *           @OA\Property(property="path",    type="string"),
		 *           @OA\Property(property="summary", type="string"),
		 *           @OA\Property(property="count",   type="integer"),
		 *           @OA\Property(property="symbols", type="array", @OA\Items(ref="#/components/schemas/DocSymbolNode"))
		 *         )
		 *       )
		 *     )
		 *   ),
		 *   @OA\Response(response="404", description="Unknown version", @OA\JsonContent(ref="#/components/schemas/DocError"))
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function tree(Request $request, null|array $matches = null) : Response {
			$ret     = $this->newResponse();
			$version = $this->requireVersion($ret, $matches[1][0] ?? '');

			if ($version === null) {
				return $ret;
			}

			$includeRemoved = $request->getGet()->getString('removed', '1') !== '0';

			$ret->setData($this->reader()->tree($version, $includeRemoved));

			return $ret;
		}

		/**
		 * A symbol at a version with its contract, prose, samples, related pages, changes, and siblings.
		 *
		 * @OA\Get(
		 *   path="/Docs/Symbol/{version}/{module}/{name}",
		 *   operationId="docsSymbol",
		 *   summary="Symbol at a version",
		 *   description="The symbol's contract at the version, its reference page, code samples, other pages covering it, changes touching it, and its siblings for the rail",
		 *   tags={"Docs"},
		 *   @OA\Parameter(name="version", in="path", required=true, description="Version label, tag, or `latest`", @OA\Schema(type="string", example="v4.2")),
		 *   @OA\Parameter(name="module",  in="path", required=true, description="Module path; may contain slashes", @OA\Schema(type="string", example="tessel/query")),
		 *   @OA\Parameter(name="name",    in="path", required=true, description="Symbol name; dotted for nested symbols", @OA\Schema(type="string", example="QueryOptions.staleTime")),
		 *   @OA\Response(
		 *     response="200",
		 *     description="OK",
		 *     @OA\JsonContent(
		 *       type="object",
		 *       @OA\Property(property="version",       ref="#/components/schemas/DocVersion"),
		 *       @OA\Property(property="module",        type="object", @OA\Property(property="path", type="string"), @OA\Property(property="summary", type="string")),
		 *       @OA\Property(property="breadcrumb",    type="array", @OA\Items(type="string")),
		 *       @OA\Property(property="symbol",        ref="#/components/schemas/DocSymbolNode"),
		 *       @OA\Property(property="relatedTypes",  type="object", description="Sibling type symbols named by parameter types, keyed by type name", additionalProperties=@OA\Schema(ref="#/components/schemas/DocSymbolNode")),
		 *       @OA\Property(property="mentions",      type="array", description="Symbols the reference page mentions, resolved at the version", @OA\Items(ref="#/components/schemas/DocSymbolNode")),
		 *       @OA\Property(property="page",          ref="#/components/schemas/DocPage", nullable=true),
		 *       @OA\Property(property="samples",       type="array", @OA\Items(ref="#/components/schemas/DocCodeSample")),
		 *       @OA\Property(property="testedSamples", type="object", @OA\Property(property="total", type="integer"), @OA\Property(property="passingAtVersion", type="integer")),
		 *       @OA\Property(property="alsoCoveredIn", type="array", @OA\Items(ref="#/components/schemas/DocPageSummary")),
		 *       @OA\Property(property="changes",       type="array", @OA\Items(ref="#/components/schemas/DocChangeSummary")),
		 *       @OA\Property(property="siblings",      type="array", @OA\Items(ref="#/components/schemas/DocSymbolNode"))
		 *     )
		 *   ),
		 *   @OA\Response(response="404", description="Unknown version or symbol not present at that version", @OA\JsonContent(ref="#/components/schemas/DocError"))
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function symbol(Request $request, null|array $matches = null) : Response {
			$ret     = $this->newResponse();
			$version = $this->requireVersion($ret, $matches[1][0] ?? '');

			if ($version === null) {
				return $ret;
			}

			$modulePath = trim($matches[2][0] ?? '', '/');
			$name       = $matches[3][0] ?? '';
			$data       = $this->reader()->symbol($version, $modulePath, $name);

			if ($data === null) {
				$ret->setAsError("Symbol '{$modulePath}#{$name}' does not exist at {$version->label}", HttpStatusCodes::NOT_FOUND);

				return $ret;
			}

			$ret->setData($data);

			return $ret;
		}

		/**
		 * A prose page at a version.
		 *
		 * @OA\Get(
		 *   path="/Docs/Page/{version}/{mode}/{slug}",
		 *   operationId="docsPage",
		 *   summary="Page at a version",
		 *   description="The page revision valid at the version, its linked symbols with their contracts, code samples, and for learn mode the course spine and steps",
		 *   tags={"Docs"},
		 *   @OA\Parameter(name="version", in="path", required=true, description="Version label, tag, or `latest`", @OA\Schema(type="string", example="v4.2")),
		 *   @OA\Parameter(name="mode",    in="path", required=true, @OA\Schema(type="string", enum={"learn", "do", "reference", "explain"})),
		 *   @OA\Parameter(name="slug",    in="path", required=true, description="Page slug; may contain slashes", @OA\Schema(type="string", example="prefetch-on-hover")),
		 *   @OA\Response(
		 *     response="200",
		 *     description="OK",
		 *     @OA\JsonContent(
		 *       type="object",
		 *       @OA\Property(property="version", ref="#/components/schemas/DocVersion"),
		 *       @OA\Property(property="page",    ref="#/components/schemas/DocPage"),
		 *       @OA\Property(property="symbols", type="array", @OA\Items(ref="#/components/schemas/DocSymbolNode")),
		 *       @OA\Property(property="samples", type="array", @OA\Items(ref="#/components/schemas/DocCodeSample")),
		 *       @OA\Property(
		 *         property="course",
		 *         type="object",
		 *         nullable=true,
		 *         description="Present for learn-mode pages that belong to a course",
		 *         @OA\Property(property="slug",          type="string"),
		 *         @OA\Property(property="title",         type="string"),
		 *         @OA\Property(property="totalMinutes",  type="integer"),
		 *         @OA\Property(property="lessonCount",   type="integer"),
		 *         @OA\Property(property="currentLesson", type="integer"),
		 *         @OA\Property(property="lessons",       type="array", @OA\Items(type="object")),
		 *         @OA\Property(property="steps",         type="array", @OA\Items(type="object"))
		 *       )
		 *     )
		 *   ),
		 *   @OA\Response(response="404", description="Unknown version or no page valid at that version", @OA\JsonContent(ref="#/components/schemas/DocError"))
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function page(Request $request, null|array $matches = null) : Response {
			$ret     = $this->newResponse();
			$version = $this->requireVersion($ret, $matches[1][0] ?? '');

			if ($version === null) {
				return $ret;
			}

			$mode = strtolower($matches[2][0] ?? '');
			$slug = trim($matches[3][0] ?? '', '/');
			$data = $this->reader()->page($version, $mode, $slug);

			if ($data === null) {
				$ret->setAsError("Page '{$mode}/{$slug}' does not exist at {$version->label}", HttpStatusCodes::NOT_FOUND);

				return $ret;
			}

			$ret->setData($data);

			return $ret;
		}

		/**
		 * The upgrade view between two versions.
		 *
		 * @OA\Get(
		 *   path="/Docs/Diff/{from}/{to}",
		 *   operationId="docsDiff",
		 *   summary="Changes between two versions",
		 *   description="Changes shipped after `from` up to and including `to`, grouped by kind, with affected symbols and computed contract diffs",
		 *   tags={"Docs"},
		 *   @OA\Parameter(name="from", in="path", required=true, description="Older version label, tag, or `latest`", @OA\Schema(type="string", example="v3.8")),
		 *   @OA\Parameter(name="to",   in="path", required=true, description="Newer version label, tag, or `latest`", @OA\Schema(type="string", example="v4.2")),
		 *   @OA\Response(
		 *     response="200",
		 *     description="OK",
		 *     @OA\JsonContent(
		 *       type="object",
		 *       @OA\Property(property="from",     ref="#/components/schemas/DocVersion"),
		 *       @OA\Property(property="to",       ref="#/components/schemas/DocVersion"),
		 *       @OA\Property(property="versions", type="array", @OA\Items(ref="#/components/schemas/DocVersion")),
		 *       @OA\Property(property="total",    type="integer"),
		 *       @OA\Property(
		 *         property="groups",
		 *         type="array",
		 *         @OA\Items(
		 *           type="object",
		 *           @OA\Property(property="kind",    type="string"),
		 *           @OA\Property(property="count",   type="integer"),
		 *           @OA\Property(
		 *             property="changes",
		 *             type="array",
		 *             @OA\Items(
		 *               allOf={
		 *                 @OA\Schema(ref="#/components/schemas/DocChangeSummary"),
		 *                 @OA\Schema(
		 *                   type="object",
		 *                   @OA\Property(property="why",           type="string"),
		 *                   @OA\Property(property="rfcUrl",        type="string", nullable=true),
		 *                   @OA\Property(property="beforeCode",    type="string", nullable=true),
		 *                   @OA\Property(property="afterCode",     type="string", nullable=true),
		 *                   @OA\Property(property="symbols",       type="array", @OA\Items(type="object")),
		 *                   @OA\Property(property="contractDiffs", type="array", @OA\Items(ref="#/components/schemas/DocContractDiff"))
		 *                 )
		 *               }
		 *             )
		 *           )
		 *         )
		 *       ),
		 *       @OA\Property(property="route", type="string", example="/upgrade/v3.8/v4.2")
		 *     )
		 *   ),
		 *   @OA\Response(response="400", description="`from` is not older than `to`", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   @OA\Response(response="404", description="Unknown version", @OA\JsonContent(ref="#/components/schemas/DocError"))
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function diff(Request $request, null|array $matches = null) : Response {
			$ret  = $this->newResponse();
			$from = $this->requireVersion($ret, $matches[1][0] ?? '');

			if ($from === null) {
				return $ret;
			}

			$to = $this->requireVersion($ret, $matches[2][0] ?? '');

			if ($to === null) {
				return $ret;
			}

			try {
				$ret->setData($this->reader()->diff($from, $to));
			} catch (\InvalidArgumentException $ex) {
				$ret->setAsError($ex->getMessage(), HttpStatusCodes::BAD_REQUEST);
			}

			return $ret;
		}

		/**
		 * Every route at a version plus search-index source records; used by the static build.
		 *
		 * @OA\Get(
		 *   path="/Docs/Manifest/{version}",
		 *   operationId="docsManifest",
		 *   summary="Build manifest for a version",
		 *   description="Every public route that exists at the version, and the records the per-version search index is generated from",
		 *   tags={"Docs"},
		 *   @OA\Parameter(name="version", in="path", required=true, description="Version label, tag, or `latest`", @OA\Schema(type="string", example="v4.2")),
		 *   @OA\Response(
		 *     response="200",
		 *     description="OK",
		 *     @OA\JsonContent(
		 *       type="object",
		 *       @OA\Property(property="version",       ref="#/components/schemas/DocVersion"),
		 *       @OA\Property(property="previous",      ref="#/components/schemas/DocVersion", nullable=true),
		 *       @OA\Property(property="routes",        type="array", @OA\Items(type="string")),
		 *       @OA\Property(property="searchRecords", type="array", @OA\Items(type="object", @OA\Property(property="type", type="string", enum={"symbol", "page", "change"}))),
		 *       @OA\Property(property="courses",       type="array", description="Courses with lessons that exist at the version, for the Learn index", @OA\Items(type="object",
		 *         @OA\Property(property="slug", type="string"),
		 *         @OA\Property(property="title", type="string"),
		 *         @OA\Property(property="summary", type="string"),
		 *         @OA\Property(property="totalMinutes", type="integer"),
		 *         @OA\Property(property="lessonCount", type="integer"),
		 *         @OA\Property(property="lessons", type="array", @OA\Items(type="object",
		 *           @OA\Property(property="ordinal", type="integer"),
		 *           @OA\Property(property="title", type="string"),
		 *           @OA\Property(property="slug", type="string"),
		 *           @OA\Property(property="minutes", type="integer", nullable=true),
		 *           @OA\Property(property="route", type="string")
		 *         ))
		 *       ))
		 *     )
		 *   ),
		 *   @OA\Response(response="404", description="Unknown version", @OA\JsonContent(ref="#/components/schemas/DocError"))
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function manifest(Request $request, null|array $matches = null) : Response {
			$ret     = $this->newResponse();
			$version = $this->requireVersion($ret, $matches[1][0] ?? '');

			if ($version === null) {
				return $ret;
			}

			$ret->setData($this->reader()->manifest($version));

			return $ret;
		}

		/**
		 * Creates the reader with the site settings attached.
		 *
		 * @return DocsReader
		 */
		protected function reader() : DocsReader {
			return new DocsReader($this->db, $this->log, $this->stoic->getConfig());
		}

		/**
		 * Resolves a version segment, setting a 404 error on the response and returning null when unknown.
		 *
		 * @param Response $ret Response to mark as an error on failure.
		 * @param string $segment Version segment from the URL.
		 * @return null|DocVersion
		 */
		protected function requireVersion(Response $ret, string $segment) : ?DocVersion {
			$version = $this->reader()->resolveVersion($segment);

			if ($version->id < 1) {
				$ret->setAsError("Unknown version '{$segment}'", HttpStatusCodes::NOT_FOUND);

				return null;
			}

			return $version;
		}
	}
