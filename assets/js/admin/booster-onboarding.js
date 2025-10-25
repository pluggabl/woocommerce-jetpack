(function($) {
	'use strict';

	var BoosterOnboarding = {
		modal: null,
		currentGoal: null,

		init: function() {
			this.modal = $( '#booster-onboarding-modal' );
			this.bindEvents();
			this.setupAccessibility();
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
				'.booster-goal-tile',
				function() {
					var goalId = $( this ).data( 'goal' );
					self.selectGoal( goalId );
				}
			);

			$( document ).on(
				'keydown',
				'.booster-goal-tile',
				function(e) {
					if (e.keyCode === 13 || e.keyCode === 32) {
						e.preventDefault();
						var goalId = $( this ).data( 'goal' );
						self.selectGoal( goalId );
					} else if (e.keyCode === 37 || e.keyCode === 38) {
						e.preventDefault();
						var prev = $( this ).prev( '.booster-goal-tile' );
						if (prev.length) {
							prev.focus();
						}
					} else if (e.keyCode === 39 || e.keyCode === 40) {
						e.preventDefault();
						var next = $( this ).next( '.booster-goal-tile' );
						if (next.length) {
							next.focus();
						}
					}
				}
			);

			$( document ).on(
				'click',
				'.back-button',
				function() {
					self.showGoalsScreen();
				}
			);

			$( document ).on(
				'click',
				'.cancel-button',
				function() {
					self.showGoalsScreen();
				}
			);

			$( document ).on(
				'click',
				'.apply-button',
				function() {
					self.applyGoal();
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
				'#pick-another-goal',
				function() {
					self.showGoalsScreen();
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

		setupAccessibility: function() {
			var self = this;

			this.modal.on(
				'keydown',
				function(e) {
					if (e.keyCode === 9) { // Tab key.
						var focusableElements = self.modal.find( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
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

		showModal: function() {
			this.modal.show();
			this.showGoalsScreen();

			setTimeout(
				function() {
					$( '.booster-goal-tile' ).first().focus();
				},
				100
			);
		},

		closeModal: function() {
			this.modal.hide();
			this.currentGoal = null;
		},

		updateProgress: function(step) {
			$('.progress-steps .step').removeClass('active completed');
			
			var stepMapping = {
				'goals': 1,
				'review': 2,
				'applying': 2,
				'success': 3
			};
			
			var currentStep = stepMapping[step] || 1;
			var progressPercent = (currentStep / 3) * 100;
			
			$('.progress-fill').css('width', progressPercent + '%');
			
			$('.progress-steps .step').each(function(index) {
				if (index < currentStep - 1) {
					$(this).addClass('completed');
				} else if (index === currentStep - 1) {
					$(this).addClass('active');
				}
			});
		},

		showGoalsScreen: function() {
			this.modal.find( '.booster-goals-screen' ).addClass( 'active' );
			this.modal.find( '.booster-review-screen, .booster-success-screen, .booster-loading-screen' ).removeClass( 'active' );
			this.updateProgress('goals');
		},

		showReviewScreen: function() {
			this.modal.find( '.booster-review-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-success-screen, .booster-loading-screen' ).removeClass( 'active' );
			this.updateProgress('review');
		},

		showSuccessScreen: function() {
			this.modal.find( '.booster-success-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-review-screen, .booster-loading-screen' ).removeClass( 'active' );
			this.updateProgress('success');
		},

		showLoadingScreen: function() {
			this.modal.find( '.booster-loading-screen' ).addClass( 'active' );
			this.modal.find( '.booster-goals-screen, .booster-review-screen, .booster-success-screen' ).removeClass( 'active' );
			this.updateProgress('applying');
		},

		selectGoal: function(goalId) {
			if ( ! boosterOnboarding.goals[goalId]) {
				return;
			}

			this.currentGoal = goalId;
			var goal         = boosterOnboarding.goals[goalId];

			$( '.booster-goal-tile' ).attr( 'aria-checked', 'false' );
			$( '.booster-goal-tile[data-goal="' + goalId + '"]' ).attr( 'aria-checked', 'true' );

			$( '#review-goal-title' ).text( goal.title );

			var modulesList = $( '#modules-list' );
			modulesList.empty();
			goal.modules.forEach(
				function(module) {
					var moduleName = module.id.replace( /_/g, ' ' ).replace(
						/\b\w/g,
						function(l) {
							return l.toUpperCase();
						}
					);
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
							$( '#success-message' ).text( response.data.message );

							if (response.data.next_steps && response.data.next_steps.length > 0) {
								var stepsList = $('#next-steps-list');
								stepsList.empty();
								response.data.next_steps.forEach(function(step) {
									stepsList.append('<li>' + step + '</li>');
								});
							}

							if (response.data.next_step_text && response.data.next_step_link) {
								$( '#next-step-text' ).text( response.data.next_step_text );
								$( '#next-step-link' ).attr( 'href', response.data.next_step_link ).show();
							}

							self.showSuccessScreen();

						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							self.showReviewScreen();
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						self.showReviewScreen();
					}
				}
			);
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
							window.location.reload();
						} else {
							alert( response.data.message || boosterOnboarding.strings.error );
							button.text( originalText ).prop( 'disabled', false );
						}
					},
					error: function() {
						alert( boosterOnboarding.strings.error );
						button.text( originalText ).prop( 'disabled', false );
					}
				}
			);
		}
	};

	$( document ).ready(
		function() {
			BoosterOnboarding.init();
		}
	);

})( jQuery );

jQuery( document ).ready(
	function($) {
		// Agar URL me hash #launch-onboarding-modal hai.
		if (window.location.hash === '#launch-onboarding-modal') {
			if (typeof BoosterOnboarding !== 'undefined') {
				BoosterOnboarding.showModal();
			}
		}

		// Agar URL me ?modal=onboarding hai.
		const urlParams = new URLSearchParams( window.location.search );
		if (urlParams.get( 'modal' ) === 'onboarding') {
			if (typeof BoosterOnboarding !== 'undefined') {
				BoosterOnboarding.showModal();
			}
		}
	}
);
