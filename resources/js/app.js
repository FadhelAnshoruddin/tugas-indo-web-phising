let deferredInstallPrompt;

window.addEventListener('beforeinstallprompt', (event) => {
	event.preventDefault();
	deferredInstallPrompt = event;
	const installButton = document.querySelector('#pwaInstallButton');
	if (installButton) installButton.hidden = false;
});

window.addEventListener('appinstalled', () => {
	deferredInstallPrompt = null;
	const installButton = document.querySelector('#pwaInstallButton');
	if (installButton) installButton.hidden = true;
});

document.addEventListener('DOMContentLoaded', () => {
	if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js').catch(() => {});

	const installButton = document.querySelector('#pwaInstallButton');
	installButton?.addEventListener('click', async () => {
		if (!deferredInstallPrompt) return;
		deferredInstallPrompt.prompt();
		await deferredInstallPrompt.userChoice;
		deferredInstallPrompt = null;
		installButton.hidden = true;
	});

	const walletCard = document.querySelector('#walletCard');
	if (walletCard) {
		const initialBalance = 80_000_000;
		const pendingKey = 'pending_claim_history_id';
		let balance = initialBalance;
		let transactionCount = 0;
		const balanceElement = document.querySelector('#balance');
		const balanceBar = document.querySelector('#balanceBar');
		const percentageElement = document.querySelector('#percentage');
		const transactionsElement = document.querySelector('#transactions');
		const warningElement = document.querySelector('#warning');
		const activityCountElement = document.querySelector('#activityCount');
		const securityBadge = document.querySelector('#securityBadge');
		const finalAlert = document.querySelector('#finalAlert');
		const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

		const claimPendingPrize = async () => {
			const pendingId = localStorage.getItem(pendingKey);
			if (!pendingId || !csrf) return;

			localStorage.removeItem(pendingKey);
			try {
				await fetch(`/spin-wheel/claim/${pendingId}`, {
					method: 'POST',
					headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
				});
			} catch (error) {
				console.error('Pending prize claim failed', error);
			}
		};

		const formatMoney = (value) => `Rp ${Math.max(0, Math.round(value)).toLocaleString('id-ID')}`;

		const render = () => {
			const percentage = (balance / initialBalance) * 100;
			balanceElement.textContent = formatMoney(balance);
			balanceBar.style.width = `${Math.max(0, percentage)}%`;
			percentageElement.textContent = `${Math.round(percentage)}%`;
			activityCountElement.textContent = `${transactionCount} aktivitas`;
		};

		const addWarning = (amount) => {
			const item = document.createElement('div');
			item.className = 'transaction-item flex items-center justify-between gap-3 rounded-xl border border-red-500/15 bg-red-500/5 p-3';
			item.innerHTML = `<div class="min-w-0"><div class="truncate text-sm font-bold">! Aktivitas Simulasi Terdeteksi</div><div class="mt-1 text-xs text-slate-500">Peringatan lokal • baru saja</div></div><div class="shrink-0 font-bold tabular-nums text-red-400">-${formatMoney(amount)}</div>`;
			transactionsElement.prepend(item);
			transactionCount += 1;
			while (transactionsElement.children.length > 12) transactionsElement.lastElementChild.remove();
		};

		const startSimulation = () => {
			const duration = 7000;
			const startTime = performance.now();
			let lastActivity = startTime;

			const animate = (currentTime) => {
				const progress = Math.min((currentTime - startTime) / duration, 1);
				const nextBalance = Math.max(0, Math.round(initialBalance * (1 - progress)));
				const amount = balance - nextBalance;
				balance = nextBalance;

				if (amount > 0 && currentTime - lastActivity >= 160) {
					addWarning(amount);
					lastActivity = currentTime;
					warningElement.textContent = 'Peringatan simulasi: aktivitas mencurigakan sedang ditampilkan untuk edukasi.';
					walletCard.classList.remove('wallet-shake');
					void walletCard.offsetWidth;
					walletCard.classList.add('wallet-shake');
				}

				render();
				if (progress < 1) {
					window.requestAnimationFrame(animate);
					return;
				}

				warningElement.textContent = 'Simulasi selesai — saldo telah mencapai Rp 0. Tidak ada transaksi nyata.';
				securityBadge.textContent = '! Simulasi selesai';
				finalAlert?.classList.remove('hidden');
				render();
			};

			window.requestAnimationFrame(animate);
		};

		render();
		claimPendingPrize().finally(() => window.setTimeout(startSimulation, 500));
		return;
	}

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
		window.location.href = '/login?redirect_to=' + encodeURIComponent('/wallet-simulation');
	});
});
