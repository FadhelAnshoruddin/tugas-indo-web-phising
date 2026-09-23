document.addEventListener('DOMContentLoaded', () => {
	const wheel = document.querySelector('#wheel');
	const spinButton = document.querySelector('#spinBtn');
	if (!wheel || !spinButton) return;

	const prizeModal = document.querySelector('#prizeModal');
	const loginModal = document.querySelector('#loginRequiredModal');
	const prizeName = document.querySelector('#prizeName');
	const claimToast = document.querySelector('#claimToast');
	const csrf = document.querySelector('meta[name="csrf-token"]').content;
	const prizes = Number(document.querySelector('[data-prizes]')?.dataset.prizes || 8);
	const segmentAngle = 360 / prizes;
	const pendingKey = 'pending_claim_history_id';
	const authenticated = window.isAuthenticated === true;
	let rotation = 0;
	let historyId = null;
	let spinning = false;

	const toggle = (element, open) => {
		element.classList.toggle('hidden', !open);
		element.classList.toggle('flex', open);
	};

	async function claimPrize(id, silent = false) {
		try {
			const response = await fetch(`/spin-wheel/claim/${id}`, {
				method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
			});
			if (!response.ok) throw new Error('claim failed');
			toggle(prizeModal, false); toggle(loginModal, false);
			claimToast.classList.remove('hidden');
			window.setTimeout(() => claimToast.classList.add('hidden'), 3600);
		} catch (error) {
			if (!silent) window.alert('Hadiah belum berhasil diklaim. Silakan coba lagi.');
		}
	}

	const pending = localStorage.getItem(pendingKey);
	if (pending && authenticated) {
		localStorage.removeItem(pendingKey);
		claimPrize(pending, true);
	}

	spinButton.addEventListener('click', async () => {
		if (spinning) return;
		spinning = true; spinButton.disabled = true; spinButton.textContent = '...';
		try {
			const response = await fetch('/spin-wheel/spin', { method: 'POST', headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' } });
			if (!response.ok) throw new Error('spin failed');
			const result = await response.json();
			historyId = result.history_id;
			rotation += 6 * 360 + (360 - (result.winner_index * segmentAngle + segmentAngle / 2));
			wheel.style.transform = `rotate(${rotation}deg)`;
			window.setTimeout(() => {
				spinning = false; spinButton.disabled = false; spinButton.textContent = 'PUTAR';
				prizeName.textContent = result.prize.name; toggle(prizeModal, true);
			}, 5200);
		} catch (error) {
			spinning = false; spinButton.disabled = false; spinButton.textContent = 'PUTAR';
			window.alert('Roda belum bisa diputar. Coba lagi.');
		}
	});

	document.querySelector('#closeModalBtn')?.addEventListener('click', () => toggle(prizeModal, false));
	document.querySelector('#claimBtn')?.addEventListener('click', () => {
		if (!historyId) return;
		if (authenticated) return claimPrize(historyId);
		localStorage.setItem(pendingKey, historyId); toggle(prizeModal, false); toggle(loginModal, true);
	});
	document.querySelector('#cancelLoginBtn')?.addEventListener('click', () => {
		localStorage.removeItem(pendingKey); toggle(loginModal, false);
	});
	document.querySelector('#goToLoginBtn')?.addEventListener('click', () => {
		window.location.href = '/login?redirect_to=' + encodeURIComponent('/spin-wheel');
	});
});
