<?php

	namespace Api1_1;

	use OpenApi\Annotations as OA;

	use Stoic\Web\Api\Response;
	use Stoic\Web\Request;
	use Stoic\Web\Resources\HttpStatusCodes;

	use Zibings\ApiController;
	use Zibings\DocsAdminException;
	use Zibings\DocsAdminWriter;
	use Zibings\RoleStrings;

	/**
	 * @OA\Schema(
	 *   schema="DocAdminResource",
	 *   type="string",
	 *   description="Documentation tables exposed for authoring",
	 *   enum={"Versions", "Modules", "Symbols", "Contracts", "Pages", "Samples", "Changes", "Courses", "Lessons", "Steps", "ContextOptions"}
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocAdminRecord",
	 *   type="object",
	 *   description="One row of a documentation table with camelCase field names; `id` is assigned by the server. Contracts carry decoded params/returns/throws; symbols carry their `ref`.",
	 *   @OA\Property(property="id", type="integer"),
	 *   additionalProperties=true
	 * )
	 *
	 * @OA\Schema(
	 *   schema="DocAdminSymbolLink",
	 *   type="object",
	 *   @OA\Property(property="symbolId", type="integer"),
	 *   @OA\Property(property="ref",      type="string", example="tessel/query#QueryOptions.staleTime"),
	 *   @OA\Property(property="name",     type="string"),
	 *   @OA\Property(property="kind",     type="string"),
	 *   @OA\Property(property="role",     type="string", enum={"subject", "mentions"}, description="Pages only")
	 * )
	 */
	class DocsAdminSchemas { }

	/**
	 * Authoring API for the documentation tables. Administrator only. Resources share one RESTful shape:
	 * GET list, POST create, GET/PUT/DELETE by id, plus reorder, mark-latest, symbol links, and bundle import/export.
	 *
	 * @OA\Tag(
	 *   name="DocsAdmin",
	 *   description="Documentation authoring endpoints (administrators)"
	 * )
	 *
	 * @package Zibings\Api1_1
	 */
	class DocsAdmin extends ApiController {
		/**
		 * Registers the controller endpoints; specific patterns first because endpoints match in order.
		 *
		 * @return void
		 */
		protected function registerEndpoints() : void {
			$admin = RoleStrings::ADMINISTRATOR;

			$this->registerEndpoint('GET',    '/^\/?Docs\/Admin\/Export\/?$/i',                          'export',           $admin);
			$this->registerEndpoint('POST',   '/^\/?Docs\/Admin\/Import\/?$/i',                          'import',           $admin);
			$this->registerEndpoint('POST',   '/^\/?Docs\/Admin\/Versions\/([0-9]+)\/Latest\/?$/i',     'markLatest',       $admin);
			$this->registerEndpoint('POST',   '/^\/?Docs\/Admin\/([A-Za-z]+)\/Reorder\/?$/i',           'reorder',          $admin);
			$this->registerEndpoint('GET',    '/^\/?Docs\/Admin\/Pages\/([0-9]+)\/Symbols\/?$/i',       'getPageSymbols',   $admin);
			$this->registerEndpoint('PUT',    '/^\/?Docs\/Admin\/Pages\/([0-9]+)\/Symbols\/?$/i',       'setPageSymbols',   $admin);
			$this->registerEndpoint('GET',    '/^\/?Docs\/Admin\/Changes\/([0-9]+)\/Symbols\/?$/i',     'getChangeSymbols', $admin);
			$this->registerEndpoint('PUT',    '/^\/?Docs\/Admin\/Changes\/([0-9]+)\/Symbols\/?$/i',     'setChangeSymbols', $admin);
			$this->registerEndpoint('GET',    '/^\/?Docs\/Admin\/([A-Za-z]+)\/([0-9]+)\/?$/i',          'get',              $admin);
			$this->registerEndpoint('PUT',    '/^\/?Docs\/Admin\/([A-Za-z]+)\/([0-9]+)\/?$/i',          'update',           $admin);
			$this->registerEndpoint('DELETE', '/^\/?Docs\/Admin\/([A-Za-z]+)\/([0-9]+)\/?$/i',          'delete',           $admin);
			$this->registerEndpoint('GET',    '/^\/?Docs\/Admin\/([A-Za-z]+)\/?$/i',                    'list',             $admin);
			$this->registerEndpoint('POST',   '/^\/?Docs\/Admin\/([A-Za-z]+)\/?$/i',                    'create',           $admin);

			return;
		}

		/**
		 * Lists a resource.
		 *
		 * @OA\Get(
		 *   path="/Docs/Admin/{resource}",
		 *   operationId="docsAdminList",
		 *   summary="List rows of a documentation table",
		 *   description="Every row, including ones removed or not yet introduced at any version. Filterable by parent (moduleId, symbolId, pageId, versionId, courseId, lessonId), mode, or kind where applicable.",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="resource", in="path", required=true, @OA\Schema(ref="#/components/schemas/DocAdminResource")),
		 *   @OA\Parameter(name="moduleId",  in="query", required=false, @OA\Schema(type="integer")),
		 *   @OA\Parameter(name="symbolId",  in="query", required=false, @OA\Schema(type="integer")),
		 *   @OA\Parameter(name="pageId",    in="query", required=false, @OA\Schema(type="integer")),
		 *   @OA\Parameter(name="versionId", in="query", required=false, @OA\Schema(type="integer")),
		 *   @OA\Parameter(name="courseId",  in="query", required=false, @OA\Schema(type="integer")),
		 *   @OA\Parameter(name="lessonId",  in="query", required=false, @OA\Schema(type="integer")),
		 *   @OA\Parameter(name="mode",      in="query", required=false, @OA\Schema(type="string")),
		 *   @OA\Parameter(name="kind",      in="query", required=false, @OA\Schema(type="string")),
		 *   @OA\Response(response="200", description="OK", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocAdminRecord"))),
		 *   @OA\Response(response="404", description="Unknown resource", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function list(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->list($matches[1][0], $request->getGet()->getSource()));
		}

		/**
		 * Creates a row.
		 *
		 * @OA\Post(
		 *   path="/Docs/Admin/{resource}",
		 *   operationId="docsAdminCreate",
		 *   summary="Create a row",
		 *   description="Body carries the row's fields in camelCase. Validation failures (duplicates, overlapping contract ranges, unknown versions) return 400 with a message.",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="resource", in="path", required=true, @OA\Schema(ref="#/components/schemas/DocAdminResource")),
		 *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/DocAdminRecord")),
		 *   @OA\Response(response="200", description="Created row", @OA\JsonContent(ref="#/components/schemas/DocAdminRecord")),
		 *   @OA\Response(response="400", description="Rejected", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function create(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->create($matches[1][0], $request->getInput()->getSource()));
		}

		/**
		 * Retrieves one row.
		 *
		 * @OA\Get(
		 *   path="/Docs/Admin/{resource}/{id}",
		 *   operationId="docsAdminGet",
		 *   summary="Get a row",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="resource", in="path", required=true, @OA\Schema(ref="#/components/schemas/DocAdminResource")),
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\Response(response="200", description="OK", @OA\JsonContent(ref="#/components/schemas/DocAdminRecord")),
		 *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function get(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->get($matches[1][0], intval($matches[2][0])));
		}

		/**
		 * Updates one row (partial updates allowed).
		 *
		 * @OA\Put(
		 *   path="/Docs/Admin/{resource}/{id}",
		 *   operationId="docsAdminUpdate",
		 *   summary="Update a row",
		 *   description="Only fields present in the body change.",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="resource", in="path", required=true, @OA\Schema(ref="#/components/schemas/DocAdminResource")),
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/DocAdminRecord")),
		 *   @OA\Response(response="200", description="Updated row", @OA\JsonContent(ref="#/components/schemas/DocAdminRecord")),
		 *   @OA\Response(response="400", description="Rejected", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function update(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->update($matches[1][0], intval($matches[2][0]), $request->getInput()->getSource()));
		}

		/**
		 * Deletes one row.
		 *
		 * @OA\Delete(
		 *   path="/Docs/Admin/{resource}/{id}",
		 *   operationId="docsAdminDelete",
		 *   summary="Delete a row",
		 *   description="Children cascade (a module's symbols, a page's samples). Rows still referenced elsewhere, such as a version with contracts, are rejected.",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="resource", in="path", required=true, @OA\Schema(ref="#/components/schemas/DocAdminResource")),
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\Response(response="200", description="Deleted", @OA\JsonContent(type="object", @OA\Property(property="deleted", type="integer"))),
		 *   @OA\Response(response="400", description="Rejected", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function delete(Request $request, null|array $matches = null) : Response {
			return $this->attempt(function () use ($matches) {
				$id = intval($matches[2][0]);

				$this->writer()->delete($matches[1][0], $id);

				return ['deleted' => $id];
			});
		}

		/**
		 * Rewrites a resource's ordering to match the given ids.
		 *
		 * @OA\Post(
		 *   path="/Docs/Admin/{resource}/Reorder",
		 *   operationId="docsAdminReorder",
		 *   summary="Reorder rows",
		 *   description="Sets sortOrder (modules, symbols, changes, courses, context options) or ordinal (lessons, steps) to the position of each id in the list, 1-based.",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="resource", in="path", required=true, @OA\Schema(ref="#/components/schemas/DocAdminResource")),
		 *   @OA\RequestBody(required=true, @OA\JsonContent(type="object", required={"ids"}, @OA\Property(property="ids", type="array", @OA\Items(type="integer")))),
		 *   @OA\Response(response="200", description="Rows in their new order", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocAdminRecord"))),
		 *   @OA\Response(response="400", description="Rejected", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function reorder(Request $request, null|array $matches = null) : Response {
			return $this->attempt(function () use ($request, $matches) {
				$ids = $request->getInput()->get('ids');

				if (!is_array($ids)) {
					throw new DocsAdminException("Body must contain an 'ids' array");
				}

				return $this->writer()->reorder($matches[1][0], $ids);
			});
		}

		/**
		 * Marks a version as the one `latest` resolves to.
		 *
		 * @OA\Post(
		 *   path="/Docs/Admin/Versions/{id}/Latest",
		 *   operationId="docsAdminMarkLatest",
		 *   summary="Mark a version as latest",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\Response(response="200", description="The version", @OA\JsonContent(ref="#/components/schemas/DocAdminRecord")),
		 *   @OA\Response(response="404", description="Not found", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function markLatest(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->markLatest(intval($matches[1][0])));
		}

		/**
		 * The symbols linked to a page.
		 *
		 * @OA\Get(
		 *   path="/Docs/Admin/Pages/{id}/Symbols",
		 *   operationId="docsAdminGetPageSymbols",
		 *   summary="Symbols linked to a page",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\Response(response="200", description="OK", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocAdminSymbolLink"))),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function getPageSymbols(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->getPageSymbols(intval($matches[1][0])));
		}

		/**
		 * Replaces the symbols linked to a page.
		 *
		 * @OA\Put(
		 *   path="/Docs/Admin/Pages/{id}/Symbols",
		 *   operationId="docsAdminSetPageSymbols",
		 *   summary="Replace a page's symbol links",
		 *   description="Body is the full list; each entry needs symbolId or ref, and a role (subject or mentions).",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\RequestBody(required=true, @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocAdminSymbolLink"))),
		 *   @OA\Response(response="200", description="The new links", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocAdminSymbolLink"))),
		 *   @OA\Response(response="400", description="Rejected", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function setPageSymbols(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->setPageSymbols(intval($matches[1][0]), $this->listBody($request)));
		}

		/**
		 * The symbols a change affects.
		 *
		 * @OA\Get(
		 *   path="/Docs/Admin/Changes/{id}/Symbols",
		 *   operationId="docsAdminGetChangeSymbols",
		 *   summary="Symbols a change affects",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\Response(response="200", description="OK", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocAdminSymbolLink"))),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function getChangeSymbols(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->getChangeSymbols(intval($matches[1][0])));
		}

		/**
		 * Replaces the symbols a change affects.
		 *
		 * @OA\Put(
		 *   path="/Docs/Admin/Changes/{id}/Symbols",
		 *   operationId="docsAdminSetChangeSymbols",
		 *   summary="Replace a change's affected symbols",
		 *   description="Body is the full list of symbol ids, refs, or objects with symbolId / ref.",
		 *   tags={"DocsAdmin"},
		 *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
		 *   @OA\RequestBody(required=true, @OA\JsonContent(type="array", @OA\Items(type="string"))),
		 *   @OA\Response(response="200", description="The new links", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/DocAdminSymbolLink"))),
		 *   @OA\Response(response="400", description="Rejected", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function setChangeSymbols(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->setChangeSymbols(intval($matches[1][0]), $this->listBody($request)));
		}

		/**
		 * Imports a documentation bundle.
		 *
		 * @OA\Post(
		 *   path="/Docs/Admin/Import",
		 *   operationId="docsAdminImport",
		 *   summary="Import a bundle",
		 *   description="Upserts every record in the bundle inside one transaction (see fixtures/README.md for the format).",
		 *   tags={"DocsAdmin"},
		 *   @OA\RequestBody(required=true, @OA\JsonContent(type="object")),
		 *   @OA\Response(response="200", description="Counts of records written by type", @OA\JsonContent(type="object", additionalProperties=@OA\Schema(type="integer"))),
		 *   @OA\Response(response="400", description="Rejected; nothing was written", @OA\JsonContent(ref="#/components/schemas/DocError")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function import(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->import($request->getInput()->getSource()));
		}

		/**
		 * Exports every documentation record as a bundle.
		 *
		 * @OA\Get(
		 *   path="/Docs/Admin/Export",
		 *   operationId="docsAdminExport",
		 *   summary="Export the whole documentation set as a bundle",
		 *   tags={"DocsAdmin"},
		 *   @OA\Response(response="200", description="Bundle", @OA\JsonContent(type="object")),
		 *   security={{"admin_header_token": {}}, {"admin_cookie_token": {}}}
		 * )
		 *
		 * @param Request $request The current request which routed to the endpoint.
		 * @param null|array $matches Array of matches returned by endpoint regex pattern.
		 * @return Response
		 */
		public function export(Request $request, null|array $matches = null) : Response {
			return $this->attempt(fn () => $this->writer()->export());
		}

		/**
		 * Runs a writer operation and maps its outcome to a response.
		 *
		 * @param callable $operation Returns the response data.
		 * @return Response
		 */
		protected function attempt(callable $operation) : Response {
			$ret = $this->newResponse();

			try {
				$ret->setData($operation());
			} catch (DocsAdminException $ex) {
				$ret->setAsError($ex->getMessage(), $ex->status);
			} catch (\Throwable $ex) {
				$this->log->error("DocsAdmin failure: {ERROR}", ['ERROR' => $ex]);
				$ret->setAsError("Unexpected error: {$ex->getMessage()}", HttpStatusCodes::INTERNAL_SERVER_ERROR);
			}

			return $ret;
		}

		/**
		 * A JSON array body, or 400.
		 *
		 * @param Request $request The request.
		 * @return array
		 */
		protected function listBody(Request $request) : array {
			$raw = $request->getRawInput();
			$decoded = is_string($raw) ? json_decode($raw, true) : null;

			if (!is_array($decoded)) {
				throw new DocsAdminException("Body must be a JSON array");
			}

			return array_values($decoded);
		}

		/**
		 * Creates the writer.
		 *
		 * @return DocsAdminWriter
		 */
		protected function writer() : DocsAdminWriter {
			return new DocsAdminWriter($this->db, $this->log);
		}
	}
