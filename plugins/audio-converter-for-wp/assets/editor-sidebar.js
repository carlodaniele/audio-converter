(function (wp) {
	if (!wp || !wp.plugins || !wp.editPost) {
		return;
	}

	var registerPlugin = wp.plugins.registerPlugin;
	var PluginSidebar = wp.editPost.PluginSidebar;
	var PluginSidebarMoreMenuItem = wp.editPost.PluginSidebarMoreMenuItem;
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var Button = wp.components.Button;
	var PanelBody = wp.components.PanelBody;
	var Notice = wp.components.Notice;
	var SelectControl = wp.components.SelectControl;
	var TextareaControl = wp.components.TextareaControl;
	var Spinner = wp.components.Spinner;
	var apiFetch = wp.apiFetch;
	var select = wp.data && wp.data.select ? wp.data.select : null;
	var dispatch = wp.data && wp.data.dispatch ? wp.data.dispatch : null;
	var createBlock = wp.blocks && wp.blocks.createBlock ? wp.blocks.createBlock : null;
	var rawHandler = wp.blocks && wp.blocks.rawHandler ? wp.blocks.rawHandler : null;
	var __ = wp.i18n && wp.i18n.__ ? wp.i18n.__ : function (text) { return text; };
	var sprintf = wp.i18n && wp.i18n.sprintf ? wp.i18n.sprintf : function (format, value) {
		return format.replace("%d", value);
	};

	var initialSettings = (window.AICBData && window.AICBData.settings) ? window.AICBData.settings : {};
	var languageOptions = (window.AICBData && Array.isArray(window.AICBData.languageOptions) && window.AICBData.languageOptions.length)
		? window.AICBData.languageOptions
		: [{ value: "en-US", label: "English (United States)" }];
	var hasRemoteLanguageCatalog = !!(window.AICBData && window.AICBData.hasRemoteLanguageCatalog);
	var fieldGroupStyle = { marginBottom: "14px" };
	var MAX_AUDIO_SIZE_BYTES = 25 * 1024 * 1024;
	var MAX_AUDIO_DURATION_SECONDS = 6 * 60;

	function toBool(value, fallbackValue) {
		if ("boolean" === typeof value) {
			return value;
		}

		if ("number" === typeof value) {
			return value > 0;
		}

		if ("string" === typeof value) {
			if ("1" === value || "true" === value) {
				return true;
			}

			if ("0" === value || "false" === value) {
				return false;
			}
		}

		return !!fallbackValue;
	}

	function parseHints(raw) {
		if (!raw || "string" !== typeof raw) {
			return [];
		}

		return raw
			.split(/\n|,/) 
			.map(function (item) {
				return item.trim();
			})
			.filter(function (item) {
				return item.length > 0;
			});
	}

	function getTargetLengthLabel(value) {
		if ("short" === value) {
			return __("short (~250-450 words)", "audio-converter-for-wp");
		}

		if ("long" === value) {
			return __("long (~900-1400 words)", "audio-converter-for-wp");
		}

		return __("medium (~500-850 words)", "audio-converter-for-wp");
	}

	function getTargetLengthHelp(value) {
		if ("short" === value) {
			return __("Approximate output length: 250 to 450 words.", "audio-converter-for-wp");
		}

		if ("long" === value) {
			return __("Approximate output length: 900 to 1400 words.", "audio-converter-for-wp");
		}

		return __("Approximate output length: 500 to 850 words.", "audio-converter-for-wp");
	}

	function countWordsFromHtml(html) {
		if (!html || "string" !== typeof html) {
			return 0;
		}

		var stripped = html
			.replace(/<[^>]*>/g, " ")
			.replace(/&nbsp;/gi, " ")
			.replace(/\s+/g, " ")
			.trim();

		if (!stripped) {
			return 0;
		}

		return stripped.split(" ").length;
	}

	function countWordsFromBlocks(blockDescriptors) {
		if (!Array.isArray(blockDescriptors) || !blockDescriptors.length) {
			return 0;
		}

		var total = 0;

		blockDescriptors.forEach(function (descriptor) {
			if (!descriptor || "object" !== typeof descriptor) {
				return;
			}

			if (descriptor.html && "string" === typeof descriptor.html) {
				total += countWordsFromHtml(descriptor.html);
			}
		});

		return total;
	}

	function parseDurationSeconds(value) {
		if ("number" === typeof value && !isNaN(value)) {
			return Math.max(0, Math.floor(value));
		}

		if ("string" !== typeof value || !value.trim()) {
			return 0;
		}

		var parts = value.split(":").map(function (item) {
			return parseInt(item, 10);
		});

		if (parts.some(function (n) { return isNaN(n); })) {
			return 0;
		}

		if (parts.length === 3) {
			return (parts[0] * 3600) + (parts[1] * 60) + parts[2];
		}

		if (parts.length === 2) {
			return (parts[0] * 60) + parts[1];
		}

		if (parts.length === 1) {
			return parts[0];
		}

		return 0;
	}

	function toInteger(value) {
		var parsed = Number(value);
		if (isNaN(parsed) || parsed <= 0) {
			return 0;
		}

		return Math.floor(parsed);
	}

	function SidebarApp() {
		var _a = useState(0), audioId = _a[0], setAudioId = _a[1];
		var _b = useState(""), audioLabel = _b[0], setAudioLabel = _b[1];
		var _b2 = useState(0), audioSizeBytes = _b2[0], setAudioSizeBytes = _b2[1];
		var _b3 = useState(0), audioDurationSeconds = _b3[0], setAudioDurationSeconds = _b3[1];
		var _c = useState(initialSettings.default_language || "en-US"), language = _c[0], setLanguage = _c[1];
		var _d = useState(initialSettings.default_tone || "professional"), tone = _d[0], setTone = _d[1];
		var _e = useState(initialSettings.default_target_length || "medium"), targetLength = _e[0], setTargetLength = _e[1];
		var _f = useState(initialSettings.default_proper_noun_hints || ""), hintsRaw = _f[0], setHintsRaw = _f[1];
		var defaultInsertionMode = "replace" === initialSettings.default_insertion_mode ? "replace" : "append";
		var _g = useState(defaultInsertionMode), insertionMode = _g[0], setInsertionMode = _g[1];
		var autoApplyTitle = toBool(initialSettings.default_auto_apply_title, true);
		var _h = useState(false), isLoading = _h[0], setIsLoading = _h[1];
		var _i = useState(null), notice = _i[0], setNotice = _i[1];

		function getAudioPreflightWarning() {
			if (audioSizeBytes > MAX_AUDIO_SIZE_BYTES) {
				return __("Selected audio file is too large (recommended max 25 MB). Please choose a smaller file or trim it before generating.", "audio-converter-for-wp");
			}

			if (audioDurationSeconds > MAX_AUDIO_DURATION_SECONDS) {
				return __("Selected audio is too long (recommended max 15 minutes). Please split or trim it before generating.", "audio-converter-for-wp");
			}

			return "";
		}

		function requestAbilityRun(payload) {
			var baseRequest = {
				method: "POST",
				headers: {
					"X-WP-Nonce": window.AICBData.nonce
				}
			};

			function isNoRoute(error) {
				if (!error) {
					return false;
				}

				if (error.code && "rest_no_route" === error.code) {
					return true;
				}

				return /No route was found/i.test(String(error.message || ""));
			}

			function call(path, data) {
				return apiFetch(Object.assign({ path: path, data: data }, baseRequest));
			}

			var primaryPath = window.AICBData.abilityRunPath;
			var altPath = window.AICBData.abilityRunPathAlt;
			var wrappedPayload = { input: payload };

			return call(primaryPath, wrappedPayload)
				.catch(function (error) {
					if (isNoRoute(error) && altPath) {
						return call(altPath, wrappedPayload);
					}

					return call(primaryPath, payload)
						.catch(function (directPrimaryError) {
							if (isNoRoute(directPrimaryError) && altPath) {
								return call(altPath, payload);
							}

							throw directPrimaryError;
						});
				});
		}

		function normalizeAbilityResponse(response) {
			if (response && response.output && "object" === typeof response.output) {
				return response.output;
			}

			return response;
		}

		function getCurrentPostId() {
			if (!select) {
				return 0;
			}

			var editorStore = select("core/editor");
			if (!editorStore || "function" !== typeof editorStore.getCurrentPostId) {
				return 0;
			}

			return Number(editorStore.getCurrentPostId()) || 0;
		}

		function injectEditorBlocks(blockDescriptors, mode) {
			if (!dispatch || !select || !createBlock || !Array.isArray(blockDescriptors) || !blockDescriptors.length) {
				return false;
			}

			var mappedBlocks = [];

			blockDescriptors.forEach(function (descriptor) {
				if (!descriptor || !descriptor.name) {
					return;
				}

				if (rawHandler && descriptor.html && "string" === typeof descriptor.html) {
					var parsed = rawHandler({ HTML: descriptor.html });
					if (Array.isArray(parsed) && parsed.length) {
						parsed.forEach(function (parsedBlock) {
							if (parsedBlock) {
								mappedBlocks.push(parsedBlock);
							}
						});
						return;
					}
				}

				mappedBlocks.push(createBlock(descriptor.name, descriptor.attributes || {}));
			});

			mappedBlocks = mappedBlocks.filter(function (block) {
				return null !== block;
			});

			if (!mappedBlocks.length) {
				return false;
			}

			var blockEditorDispatch = dispatch("core/block-editor");
			if (!blockEditorDispatch) {
				return false;
			}

			if ("replace" === mode) {
				var blockEditorSelect = select("core/block-editor");
				if (blockEditorSelect && "function" === typeof blockEditorSelect.getBlocks && "function" === typeof blockEditorDispatch.replaceBlocks) {
					var currentBlocks = blockEditorSelect.getBlocks();
					var currentClientIds = Array.isArray(currentBlocks)
						? currentBlocks.map(function (block) { return block.clientId; }).filter(Boolean)
						: [];

					if (currentClientIds.length) {
						blockEditorDispatch.replaceBlocks(currentClientIds, mappedBlocks);
						return true;
					}
				}
			}

			if ("function" === typeof blockEditorDispatch.insertBlocks) {
				blockEditorDispatch.insertBlocks(mappedBlocks);
				return true;
			}

			return false;
		}

		function applyEditorTitle(title) {
			if (!dispatch || !title || "string" !== typeof title) {
				return;
			}

			var trimmed = title.trim();
			if (!trimmed) {
				return;
			}

			var editorDispatch = dispatch("core/editor");
			if (editorDispatch && "function" === typeof editorDispatch.editPost) {
				editorDispatch.editPost({ title: trimmed });
			}
		}

		function openMediaLibrary() {
			if (!wp.media) {
				setNotice({ status: "error", message: __("WordPress media library is unavailable.", "audio-converter-for-wp") });
				return;
			}

			var frame = wp.media({
				title: __("Select audio", "audio-converter-for-wp"),
				button: { text: __("Use this audio", "audio-converter-for-wp") },
				library: { type: "audio" },
				multiple: false
			});

			frame.on("select", function () {
				var selection = frame.state().get("selection").first();
				if (!selection) {
					return;
				}

				var media = selection.toJSON();
				setAudioId(media.id || 0);
				setAudioLabel(media.title || media.filename || sprintf(__("Audio #%d", "audio-converter-for-wp"), media.id));

				var sizeBytes = toInteger(media.filesizeInBytes);
				if (!sizeBytes && media.filesize && "object" === typeof media.filesize) {
					sizeBytes = toInteger(media.filesize.raw);
				}
				setAudioSizeBytes(sizeBytes);

				var durationSeconds = toInteger(media.length);
				if (!durationSeconds) {
					durationSeconds = parseDurationSeconds(media.fileLength);
				}
				setAudioDurationSeconds(durationSeconds);

				setNotice(null);
			});

			frame.open();
		}

		function generateFromAudio() {
			var preflightWarning = getAudioPreflightWarning();
			if (preflightWarning) {
				setNotice({ status: "warning", message: preflightWarning });
				return;
			}

			if (!window.AICBData || !window.AICBData.abilityRunPath || !window.AICBData.nonce) {
				setNotice({ status: "error", message: __("Plugin configuration missing in editor context.", "audio-converter-for-wp") });
				return;
			}

			if (!audioId) {
				setNotice({ status: "warning", message: __("Select an audio file first.", "audio-converter-for-wp") });
				return;
			}

			setIsLoading(true);
			setNotice(null);

			var publishOptions = {
				status: "draft",
				post_type: "post"
			};

			var currentPostId = getCurrentPostId();
			if (currentPostId > 0) {
				publishOptions.target_post_id = currentPostId;
			}

			var payload = {
				contract_version: "1.0.0",
				external_run_id: "wp-editor-" + Date.now(),
				source: "manual",
				audio: { media_id: audioId },
				editorial_options: {
					language: language,
					tone: tone,
					target_length: targetLength
				},
				proper_noun_hints: parseHints(hintsRaw),
				publish_options: publishOptions
			};

			requestAbilityRun(payload)
				.then(function (response) {
					response = normalizeAbilityResponse(response);

					if (!response || response.status === "failed") {
						var msg = response && response.error && response.error.message ? response.error.message : __("Generation failed.", "audio-converter-for-wp");
						setNotice({ status: "error", message: msg });
						return;
					}

					if (response.status !== "completed") {
						setNotice({ status: "info", message: __("Run accepted. Current status:", "audio-converter-for-wp") + " " + response.status });
						return;
					}

					var hasInjectedBlocks = injectEditorBlocks(response.blocks || [], insertionMode);
					if (autoApplyTitle) {
						applyEditorTitle(response.title);
					}

					if (!hasInjectedBlocks) {
						setNotice({ status: "warning", message: __("No blocks were inserted into the editor.", "audio-converter-for-wp") });
						return;
					}

					var wordCount = countWordsFromBlocks(response.blocks || []);
					if (wordCount > 0) {
						setNotice({
							status: "success",
							message: sprintf(__("Content generated successfully. Approximate word count: %d.", "audio-converter-for-wp"), wordCount)
						});
						return;
					}

					setNotice({ status: "success", message: __("Content generated successfully.", "audio-converter-for-wp") });
				})
				.catch(function (error) {
					setNotice({
						status: "error",
						message: error && error.message ? error.message : __("Unexpected request error.", "audio-converter-for-wp")
					});
				})
				.finally(function () {
					setIsLoading(false);
				});
		}

		return el(
			"div",
			null,
			notice ? el(Notice, { status: notice.status, isDismissible: true, onRemove: function () { setNotice(null); } }, notice.message) : null,
			el(
				PanelBody,
				{ title: __("Audio source", "audio-converter-for-wp"), initialOpen: true },
				el(Button, { variant: "secondary", onClick: openMediaLibrary }, __("Select audio from Media Library", "audio-converter-for-wp")),
				el(
					"p",
					{ style: { marginTop: "8px", marginBottom: "8px", color: "#757575", fontSize: "12px", lineHeight: "1.4" } },
					__("Recommended limits: up to 6 minutes and 25 MB to reduce timeout risk.", "audio-converter-for-wp")
				),
				audioLabel
					? el(
						"p",
						{
							style: {
								marginTop: "8px",
								display: "flex",
								alignItems: "center",
								gap: "8px",
								fontWeight: 600
							}
						},
						el("span", {
							"aria-hidden": true,
							style: {
								width: "10px",
								height: "10px",
								borderRadius: "999px",
								backgroundColor: "#2fb344",
								boxShadow: "0 0 0 2px rgba(47,179,68,0.2)"
							}
						}),
						__("Selected:", "audio-converter-for-wp") + " " + audioLabel
					)
					: el("p", null, __("No audio selected", "audio-converter-for-wp"))
			),
			el(
				PanelBody,
				{ title: __("Editorial options", "audio-converter-for-wp"), initialOpen: false },
				el(
					"div",
					{ style: fieldGroupStyle },
					el(SelectControl, {
						label: __("Language", "audio-converter-for-wp"),
						value: language,
						options: languageOptions,
						onChange: setLanguage,
						help: hasRemoteLanguageCatalog
							? __("Choose the default output language.", "audio-converter-for-wp")
							: __("Showing installed languages only. Remote WordPress language catalog is currently unavailable.", "audio-converter-for-wp")
					})
				),
				el(
					"div",
					{ style: fieldGroupStyle },
					el(SelectControl, {
						label: __("Tone", "audio-converter-for-wp"),
						value: tone,
						options: [
							{ label: __("professional", "audio-converter-for-wp"), value: "professional" },
							{ label: __("neutral", "audio-converter-for-wp"), value: "neutral" },
							{ label: __("conversational", "audio-converter-for-wp"), value: "conversational" }
						],
						help: __("Defines the writing style used for the generated draft.", "audio-converter-for-wp"),
						onChange: setTone
					})
				),
				el(
					"div",
					{ style: fieldGroupStyle },
					el(SelectControl, {
						label: __("Target length", "audio-converter-for-wp"),
						value: targetLength,
						options: [
							{ label: getTargetLengthLabel("short"), value: "short" },
							{ label: getTargetLengthLabel("medium"), value: "medium" },
							{ label: getTargetLengthLabel("long"), value: "long" }
						],
						help: getTargetLengthHelp(targetLength),
						onChange: setTargetLength
					})
				),
				el(
					"div",
					{ style: fieldGroupStyle },
					el(TextareaControl, {
						label: __("Proper noun hints (comma or new line separated)", "audio-converter-for-wp"),
						value: hintsRaw,
						onChange: setHintsRaw,
						help: __("Use this field to protect names that must remain accurate (people, places, brands, products). Example: Marrakech, BMW GS.", "audio-converter-for-wp"),
						rows: 4
					})
				),
				el(
					"div",
					{ style: { marginBottom: 0 } },
					el(SelectControl, {
						label: __("Editor insertion mode", "audio-converter-for-wp"),
						value: insertionMode,
						options: [
							{ label: __("Append (recommended)", "audio-converter-for-wp"), value: "append" },
							{ label: __("Replace", "audio-converter-for-wp"), value: "replace" }
						],
						help: __("Append adds new blocks at the end. Replace overwrites current editor blocks.", "audio-converter-for-wp"),
						onChange: setInsertionMode
					})
				)
			),
			el(
				PanelBody,
				{ title: __("Generate", "audio-converter-for-wp"), initialOpen: true },
				getAudioPreflightWarning() ? el(Notice, { status: "warning", isDismissible: false }, getAudioPreflightWarning()) : null,
				el(
					"p",
					{ style: { marginTop: 0, marginBottom: "8px", color: "#50575e" } },
					audioId ? __("Audio selected. Ready to generate.", "audio-converter-for-wp") : __("Select an audio file to enable generation.", "audio-converter-for-wp")
				),
				el(
					Button,
					{
						variant: "primary",
						disabled: isLoading || !audioId || !!getAudioPreflightWarning(),
						onClick: generateFromAudio,
						style: { width: "100%", justifyContent: "center" }
					},
					isLoading ? __("Generating...", "audio-converter-for-wp") : __("Generate draft from audio", "audio-converter-for-wp")
				),
				isLoading ? el("p", { style: { marginTop: "8px", marginBottom: 0 } }, el(Spinner, null)) : null
			)
		);
	}

	function SidebarRoot() {
		return el(
			wp.element.Fragment,
			null,
			el(PluginSidebarMoreMenuItem, { target: "audio-converter-sidebar" }, __("Audio Converter", "audio-converter-for-wp")),
			el(PluginSidebar, { name: "audio-converter-sidebar", title: __("Audio Converter", "audio-converter-for-wp") }, el(SidebarApp, null))
		);
	}

	registerPlugin("audio-converter-for-wp-sidebar", {
		render: SidebarRoot
	});
})(window.wp);
