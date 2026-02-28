(function($) {
	'use strict';

	var BoosterOnboarding = {
		modal: null,
		currentGoal: null,
		currentBlueprint: null,
		currentMode: 'goals',

		init: function() {
			this.modal = $( '#booster-onboarding-modal' );
			this.bindEvents();
			this.setupAccessibility();
			this.loadModePreference();
			this.updateAppliedBadges();
			this.initSearch();
		},

		bindEvents: function() {
			var self = this;

			$( document ).on(
				'click',
				'#launch-onboarding-modal',
				function() {
					self.showModal();
				}
			);

			$( document ).on(
				'click',
				'.booster-modal-close, .booster-modal-overlay',
				function() {
					self.closeModal();
				}
			);

			$( document ).on(
				'click',
				'.segment-button',
				function() {
					var mode = $( this ).data( 'mode' );
					self.switchMode( mode );
				}
			);

			$( document ).on(
				'click',
				'.booster-goal-tile',
				function() {
					var goalId = $( this ).data( 'goal' );
					self.selectGoal( goalId );
				}
			);

			$( document ).on(
				'keydown',
				'.booster-goal-tile, .booster-blueprint-tile',
				function(e) {
					self.handleTileKeyboardNav( e, $( this ) );
				}
			);

			$( document ).on(
				'click',
				'.booster-blueprint-tile',
				function() {
					var blueprintId = $( this ).data( 'blueprint' );
					self.selectBlueprint( blueprintId );
				}
			);

			$( document ).on(
				'click',
				'.back-button',
				function() {
					self.showChooseScreen();
				}
			);

			$( document ).on(
				'click',
				'.cancel-button',
				function() {
					self.showChooseScreen();
				}
			);

			$( document ).on(
				'click',
				'.apply-button',
				function() {
					if (self.currentGoal) {
						self.applyGoal();
					} else if (self.currentBlueprint) {
						self.applyBlueprint();
					}
				}
			);

			$( document ).on(
				'click',
				'.close-button',
				function() {
					self.closeModal();
				}
			);

			$( document ).on(
				'click',
				'.pick-another-button',
				function() {
					self.showChooseScreen();
				}
			);

			$( document ).on(
				'click',
				'.undo-goal',
				function() {
					var goalId = $( this ).data( 'goal' );
					self.undoGoal( goalId );
				}
			);

			$( document ).on(
				'keydown',
				function(e) {
					if (e.keyCode === 27 && self.modal.is( ':visible' )) {
						self.closeModal();
					}
				}
			);
		},

		handleTileKeyboardNav: function(e, tile) {
			var keyCode = e.keyCode || e.which;

			if (keyCode === 13 || keyCode === 32) {
				e.preventDefault();
				tile.trigger( 'click' );
				return;
			}

			if (keyCode !== 37 && keyCode !== 38 && keyCode !== 39 && keyCode !== 40) {
				return;
			}

			e.preventDefault();

			var selector = tile.hasClass( 'booster-blueprint-tile' ) ? '.booster-blueprint-tile:visible' : '.booster-goal-tile:visible';
			var visibleTiles = this.modal.find( selector );
			var currentIndex = visibleTiles.index( tile );
			var nextIndex = currentIndex;

			if (keyCode === 37 || keyCode === 38) {
				nextIndex = currentIndex - 1;
			} else if (keyCode === 39 || keyCode === 40) {
				nextIndex = currentIndex + 1;
			}

			if (nextIndex >= 0 && nextIndex < visibleTiles.length) {
				visibleTiles.eq( nextIndex ).focus();
			}
		},

		setupAccessibility: function() {
			var self = this;

			this.modal.on(
				'keydown',
				function(e) {
					if (e.keyCode === 9) {
						var focusableElements = self.modal.find( 'button:visible, [href]:visible, input:visible, select:visible, textarea:visible, [tabindex]:not([tabindex="-1"]):visible' );
						var firstElement      = focusableElements.first();
						var lastElement       = focusableElements.last();

						if (e.shiftKey) {
							if ($( document.activeElement ).is( firstElement )) {
								e.preventDefault();
								lastElement.focus();
							}
						} else {
							if ($( document.activeElement ).is( lastElement )) {
								e.preventDefault();
								firstElement.focus();
							}
						}
					}
				}
			);
		},

		loadModePreference: function() {
			if (typeof localStorage !== 'undefined') {
				var savedMode = localStorage.getItem( 'booster_onboarding_mode' );
				if (savedMode && (savedMode === 'goals' || savedMode === 'blueprints')) {
					this.currentMode = savedMode;
				}
			}
		},

		saveModePreference: function(mode) {
			if (typeof localStorage !== 'undefined') {
				localStorage.setItem( 'booster_onboarding_mode', mode );
			}
		},

		switchMode: function(mode) {
			this.currentMode = mode;
			this.saveModePreference( mode );

			$( '.segment-button' ).removeClass( 'active' ).attr( 'aria-selected', 'false' );
			$( '.segment-button[data-mode="' + mode + '"]' ).addClass( 'active' ).attr( 'aria-selected', 'true' );

			if (mode === 'goals') {
				$( '.booster-goals-screen' ).addClass( 'active' );
				$( '.booster-blueprints-screen' ).removeClass( 'active' );
			} else {
				$( '.booster-blueprints-screen' ).addClass( 'active' );
				$( '.booster-goals-screen' ).removeClass( 'active' );
			}

			this.logEvent( 'mode_switch', { mode: mode } );
		},

		updateAppliedBadges: function() {
			if (typeof boosterOnboarding.appliedGoals !== 'undefined') {
				boosterOnboarding.appliedGoals.forEach(
					function(goalId) {
						$( '.booster-goal-tile[data-goal="' + goalId + '"]' )
							.attr( 'aria-pressed', 'true' )
							.find( '.applied-badge' )
							.show();
					}
				);
			}
		},

		markGoalApplied: function(goalId) {
			if ( ! goalId) {
				return;
			}

			if (typeof boosterOnboarding.appliedGoals === 'undefined' || ! Array.isArray( boosterOnboarding.appliedGoals )) {
				boosterOnboarding.appliedGoals = [];
			}

			if (boosterOnboarding.appliedGoals.indexOf( goalId ) === -1) {
				boosterOnboarding.appliedGoals.push( goalId );
			}

			$( '.booster-goal-tile[data-goal="' + goalId + '"]' )
				.attr( 'aria-pressed', 'true' )
				.find( '.applied-badge' )
				.show();
		},

		markBlueprintApplied: function(blueprintId) {
			if ( ! blueprintId) {
				return;
			}

			$( '.booster-blueprint-tile[data-blueprint="' + blueprintId + '"]' )
				.attr( 'aria-pressed', 'true' )
				.find( '.applied-badge' )
				.show();

			if (boosterOnboarding.blueprints && boosterOnboarding.blueprints[blueprintId] && boosterOnboarding.blueprints[blueprintId].goal_keys) {
				boosterOnboarding.blueprints[blueprintId].goal_keys.forEach(
					function(goalId) {
						this.markGoalApplied( goalId );
					}.bind( this )
				);
			}
		},

		updateProgressIndicator: function(step) {
			$( '.progress-step' ).removeClass( 'active completed' );
			var stepMap = {
				choose: 1,
				review: 2,
				complete: 3
			};
			this.modal.find( '.booster-progress-indicator' ).attr( 'aria-valuenow', stepMap[step] || 1 );

			if (step === 'choose') {
				$( '.progress-step[data-step="choose"]' ).addClass( 'active' );
			} else if (step === 'review') {
				$( '.progress-step[data-step="choose"]' ).addClass( 'completed' );
				$( '.progress-step[data-step="review"]' ).addClass( 'active' );
			} else if (step === 'complete') {
				$( '.progress-step[data-step="choose"]' ).addClass( 'completed' );
				$( '.progress-step[data-step="review"]' ).addClass( 'completed' );
				$( '.progress-step[data-step="complete"]' ).addClass( 'active' );
			}
		},

		showModal: function() {
			this.modal.show();
			this.showChooseScreen();
			this.switchMode( this.currentMode );

			this.logEvent( 'modal_open', {} );

			setTimeout(
				function() {
					if (this.currentMode === 'goals') {
						$( '.booster-goal-tile' ).first().focus();
					} else {
						$( '.booster-blueprint-tile' ).first().focus();
					}
				}.bind( this ),
				100
			);
		},

		closeModal: function() {
			this.modal.hide();
			this.currentGoal = null;
			this.currentBlueprint = null;
			this.logEvent( 'modal_close', {} );
		},

		showChooseScreen: function() {
			this.updateProgressIndicator( 'choose' );

			if (this.currentMode === 'goals') {
				this.modal.find( '.booster-goals-screen' ).addClass( 'active' );
				this.modal.find( '.booster-blueprints-screen' ).removeClass( 'active' );
			} else {
				this.modal.find( '.booster-blueprints-screen' ).addClass( 'active' );
				this.modal.find( '.booster-goals-screen' ).removeClass( 'active' );
			}

			this.modal.find( '.booster-review-screen, .booster-success-screen, .booster-loading-screen' ).removeClass( 'active' );
		},

		showReviewScreen: function() {
			this.updateProgressIndicator( 'review' );
			this.modal.find( '.booster-review-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-blueprints-screen, .booster-success-screen, .booster-loading-screen' ).removeClass( 'active' );
		},

		showSuccessScreen: function() {
			this.updateProgressIndicator( 'complete' );
			this.modal.find( '.booster-success-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-blueprints-screen, .booster-review-screen, .booster-loading-screen' ).removeClass( 'active' );
		},

		showLoadingScreen: function() {
			this.modal.find( '.booster-loading-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-blueprints-screen, .booster-review-screen, .booster-success-screen' ).removeClass( 'active' );
		},

		selectGoal: function(goalId) {
			if ( ! boosterOnboarding.goals[goalId]) {
				return;
			}

			this.currentGoal = goalId;
			this.currentBlueprint = null;
			var goal         = boosterOnboarding.goals[goalId];
			$( '.booster-goal-tile' ).attr( 'aria-pressed', 'false' );
			$( '.booster-goal-tile[data-goal="' + goalId + '"]' ).attr( 'aria-pressed', 'true' );

			$( '#review-goal-title' ).text( goal.title );

			var modulesList = $( '#modules-list' );
			modulesList.empty();
			goal.modules.forEach(
				function(module) {
					var moduleName = module.name;
					modulesList.append( '<li>' + moduleName + '</li>' );
				}
			);

			var settingsList = $( '#settings-list' );
			settingsList.empty();
			goal.modules.forEach(
				function(module) {
					Object.keys( module.settings ).forEach(
						function(key) {
							if (key === 'create_demo_draft') {
								settingsList.append( '<li>' + boosterOnboarding.strings.create_demo_draft + '</li>' );
							} else if (key === 'add_one_extra') {
								settingsList.append( '<li>' + boosterOnboarding.strings.add_one_extra + '</li>' );
							} else {
								var value = module.settings[key];
								if (typeof value === 'object') {
									value = JSON.stringify( value );
								}
								settingsList.append( '<li>' + key + ': ' + value + '</li>' );
							}
						}
					);
				}
			);

			this.logEvent( 'goal_select', { goal_id: goalId } );
			this.showReviewScreen();
		},

		selectBlueprint: function(blueprintId) {
			if ( ! boosterOnboarding.blueprints[blueprintId]) {
				return;
			}

			this.currentBlueprint = blueprintId;
			this.currentGoal = null;
			var blueprint = boosterOnboarding.blueprints[blueprintId];
			$( '.booster-blueprint-tile' ).attr( 'aria-pressed', 'false' );
			$( '.booster-blueprint-tile[data-blueprint="' + blueprintId + '"]' ).attr( 'aria-pressed', 'true' );

			$( '#review-goal-title' ).text( blueprint.title );

			var modulesList = $( '#modules-list' );
			modulesList.empty();
			modulesList.append( '<li>' + blueprint.description + '</li>' );

			var settingsList = $( '#settings-list' );
			settingsList.empty();
			settingsList.append( '<li><strong>Includes ' + blueprint.goal_keys.length + ' goals:</strong></li>' );
			blueprint.goal_keys.forEach(
				function(goalKey) {
					if (boosterOnboarding.goals[goalKey]) {
						settingsList.append( '<li>• ' + boosterOnboarding.goals[goalKey].title + '</li>' );
					}
				}
			);

			this.logEvent( 'blueprint_select', { blueprint_id: blueprintId } );
			this.showReviewScreen();
		},

		applyGoal: function() {
			if ( ! this.currentGoal) {
				return;
			}

			var self           = this;
			var createSnapshot = $( '#create-snapshot' ).is( ':checked' );

			this.showLoadingScreen();
			$( '#loading-message' ).text( boosterOnboarding.strings.applying );

			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_apply_goal',
						goal_id: this.currentGoal,
						create_snapshot: createSnapshot,
						nonce: boosterOnboarding.nonce
					},
					success: function(response) {
						if (response.success) {
							self.showGoalSuccessScreen( response.data );
							self.logEvent( 'goal_apply_success', { goal_id: self.currentGoal } );
						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							self.showReviewScreen();
							self.logEvent( 'goal_apply_error', { goal_id: self.currentGoal, error: response.data.message } );
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						self.showReviewScreen();
						self.logEvent( 'goal_apply_error', { goal_id: self.currentGoal, error: 'ajax_error' } );
					}
				}
			);
		},

		applyBlueprint: function() {
			if ( ! this.currentBlueprint) {
				return;
			}

			var self           = this;
			var createSnapshot = $( '#create-snapshot' ).is( ':checked' );

			this.showLoadingScreen();
			$( '#loading-message' ).text( boosterOnboarding.strings.applying );

			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_apply_blueprint',
						blueprint_id: this.currentBlueprint,
						create_snapshot: createSnapshot,
						nonce: boosterOnboarding.nonce
					},
					success: function(response) {
						if (response.success) {
							self.showBlueprintSuccessScreen( response.data );
							self.logEvent( 'blueprint_apply_success', { blueprint_id: self.currentBlueprint } );
						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							self.showReviewScreen();
							self.logEvent( 'blueprint_apply_error', { blueprint_id: self.currentBlueprint, error: response.data.message } );
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						self.showReviewScreen();
						self.logEvent( 'blueprint_apply_error', { blueprint_id: self.currentBlueprint, error: 'ajax_error' } );
					}
				}
			);
		},

		showGoalSuccessScreen: function(data) {
			// E4: First Win Celebration - show special message on first-ever goal apply.
			if (data.first_win) {
				$( '#success-message' ).html(
					'<div class="first-win-celebration" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px; border-radius: 8px; margin-bottom: 16px; text-align: center;">' +
					'<span style="font-size: 32px; display: block; margin-bottom: 8px;">🎉</span>' +
					'<strong style="font-size: 18px; display: block; margin-bottom: 4px;">' + boosterOnboarding.strings.firstWinTitle + '</strong>' +
					'<span style="opacity: 0.9;">' + boosterOnboarding.strings.firstWinMessage + '</span>' +
					'</div>'
				);
				this.logEvent( 'first_win_celebration', { goal_id: this.currentGoal } );
			} else {
				$( '#success-message' ).text( data.message );
			}

			$( '#next-steps-container' ).hide();
			$( '#pro-note-container' ).hide();
			$( '.primary-cta-button' ).hide();

			if (data.next_steps && data.next_steps.length > 0) {
				var nextStepsList = $( '#next-steps-list' );
				nextStepsList.empty();
				data.next_steps.forEach(
					function(step) {
						if (typeof step === 'object' && step.href && step.label) {
							nextStepsList.append( '<li><a href="' + step.href + '">' + step.label + '</a></li>' );
						} else {
							nextStepsList.append( '<li>' + step + '</li>' );
						}
					}
				);
				$( '#next-steps-container' ).show();
			}

			if (data.next_step_text && data.next_step_link) {
				$( '#primary-cta-text' ).text( data.next_step_text );
				$( '.primary-cta-button' ).attr( 'href', data.next_step_link ).show();
			}

			this.showSuccessScreen();
			this.markGoalApplied( this.currentGoal );
			this.logEvent( 'goal_completed', { goal_id: this.currentGoal } );
		},

		showBlueprintSuccessScreen: function(data) {
			$( '#success-message' ).text( data.message );

			if (data.next_steps && data.next_steps.length > 0) {
				var nextStepsList = $( '#next-steps-list' );
				nextStepsList.empty();
				data.next_steps.forEach(
					function(step) {
						if (step.href && step.label) {
							nextStepsList.append( '<li><a href="' + step.href + '">' + step.label + '</a></li>' );
						} else {
							nextStepsList.append( '<li>' + step + '</li>' );
						}
					}
				);
				$( '#next-steps-container' ).show();
			} else {
				$( '#next-steps-container' ).hide();
			}

			if (data.pro_note) {
				$( '#pro-note-link' ).text( data.pro_note.label ).attr( 'href', data.pro_note.href );
				$( '#pro-note-container' ).show();
			} else {
				$( '#pro-note-container' ).hide();
			}

			if (data.primary_cta) {
				$( '#primary-cta-text' ).text( data.primary_cta.label );
				$( '.primary-cta-button' ).attr( 'href', data.primary_cta.href ).show();
			} else {
				$( '.primary-cta-button' ).hide();
			}

			this.showSuccessScreen();
			this.markBlueprintApplied( this.currentBlueprint );
		},

		undoGoal: function(goalId) {
			var self = this;

			if ( ! confirm( boosterOnboarding.strings.confirmUndo )) {
				return;
			}

			var button       = $( '.undo-goal[data-goal="' + goalId + '"]' );
			var originalText = button.text();
			button.text( boosterOnboarding.strings.undoing ).prop( 'disabled', true );

			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_undo_goal',
						goal_id: goalId,
						nonce: boosterOnboarding.nonce
					},
					success: function(response) {
						if (response.success) {
							alert( response.data.message );
							self.logEvent( 'goal_undo_success', { goal_id: goalId } );
							window.location.reload();
						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							button.text( originalText ).prop( 'disabled', false );
							self.logEvent( 'goal_undo_error', { goal_id: goalId, error: response.data.message } );
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						button.text( originalText ).prop( 'disabled', false );
						self.logEvent( 'goal_undo_error', { goal_id: goalId, error: 'ajax_error' } );
					}
				}
			);
		},

		logEvent: function(eventType, eventData) {
			$.ajax(
				{
					url: boosterOnboarding.ajaxUrl,
					type: 'POST',
					data: {
						action: 'booster_log_onboarding_event',
						event_type: eventType,
						event_data: eventData,
						nonce: boosterOnboarding.nonce
					}
				}
			);
		},

		initSearch: function() {
			var self = this;
			var searchInput = $( '#booster-goal-search' );
			var clearButton = $( '.booster-search-clear' );
			var noResults = $( '.booster-no-results' );
			var debounceTimer = null;

			searchInput.on(
				'input',
				function() {
					var query = $( this ).val().toLowerCase().trim();

					// Show/hide clear button.
					if (query.length > 0) {
						clearButton.show();
					} else {
						clearButton.hide();
					}

					// Debounce the search.
					clearTimeout( debounceTimer );
					debounceTimer = setTimeout(
						function() {
							self.filterGoals( query );
						},
						150
					);
				}
			);

			clearButton.on(
				'click',
				function() {
					searchInput.val( '' ).trigger( 'input' ).focus();
				}
			);

			// Clear search when switching modes or closing modal.
			$( document ).on(
				'click',
				'.segment-button, .booster-modal-close, .booster-modal-overlay, .pick-another-button, .back-button',
				function() {
					searchInput.val( '' );
					clearButton.hide();
					noResults.hide();
					$( '.booster-goal-tile' ).show();
				}
			);
		},

		filterGoals: function(query) {
			var goalTiles = $( '.booster-goal-tile' );
			var noResults = $( '.booster-no-results' );
			var visibleCount = 0;

			if (query === '') {
				goalTiles.show();
				noResults.hide();
				return;
			}

			goalTiles.each(
				function() {
					var tile = $( this );
					var title = tile.find( 'h3' ).text().toLowerCase();
					var subtitle = tile.find( 'p' ).text().toLowerCase();
					var modules = [];

					tile.find( '.module-tag' ).each(
						function() {
							modules.push( $( this ).text().toLowerCase() );
						}
					);

					var modulesText = modules.join( ' ' );
					var searchText = title + ' ' + subtitle + ' ' + modulesText;

					if (searchText.indexOf( query ) !== -1) {
						tile.show();
						visibleCount++;
					} else {
						tile.hide();
					}
				}
			);

			if (visibleCount === 0) {
				noResults.show();
			} else {
				noResults.hide();
			}

			this.logEvent( 'goal_search', { query: query, results_count: visibleCount } );
		}
	};

	$( document ).ready(
		function() {
			BoosterOnboarding.init();
		}
	);

	window.BoosterOnboarding = BoosterOnboarding;

})( jQuery );

jQuery( document ).ready(
	function($) {
		if (window.location.hash === '#launch-onboarding-modal') {
			if (typeof BoosterOnboarding !== 'undefined') {
				BoosterOnboarding.showModal();
			}
		}

		const urlParams = new URLSearchParams( window.location.search );
		if (urlParams.get( 'modal' ) === 'onboarding') {
			if (typeof BoosterOnboarding !== 'undefined') {
				BoosterOnboarding.showModal();
			}
		}
	}
);
