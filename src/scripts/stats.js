/* global smtpReportData */
import Chart from 'chart.js/auto';

export function mailChartsSMTP() {
	if (
		typeof smtpReportData !== 'undefined' &&
		0 !== Object.keys( smtpReportData ).length
	) {
		const cf7aCharts = {};
		smtpReportData.lineData = {
			success: {},
			failed: {},
		};
		smtpReportData.pieData = {
			success: 0,
			failed: 0,
		};

		for ( const timestamp in smtpReportData.storage ) {
			const day = new Date( timestamp * 1000 )
				.setHours( 0, 0, 0, 0 )
				.valueOf();

			if (
				typeof smtpReportData.lineData.failed[ day ] === 'undefined'
			) {
				smtpReportData.lineData.failed[ day ] = 0;
			}
			if (
				typeof smtpReportData.lineData.success[ day ] === 'undefined'
			) {
				smtpReportData.lineData.success[ day ] = 0;
			}

			if ( smtpReportData.storage[ timestamp ].mail_sent === true ) {
				smtpReportData.lineData.success[ day ]++;
				smtpReportData.pieData.success++;
			} else {
				smtpReportData.lineData.failed[ day ]++;
				smtpReportData.pieData.failed++;
			}
		}

		const lineConfig = {
			type: 'line',
			data: {
				datasets: [
					{
						label: 'Failed',
						data: Object.values( smtpReportData.lineData.failed ),
						fill: false,
						borderColor: 'rgb(255, 99, 132)',
						tension: 0.1,
					},
					{
						label: 'Success',
						data: Object.values( smtpReportData.lineData.success ),
						fill: false,
						borderColor: 'rgb(54, 162, 235)',
						tension: 0.1,
					},
				],
				labels: Object.keys( smtpReportData.lineData.failed ).map(
					( label ) => {
						const step = new Date( parseInt( label, 10 ) );
						return step.toLocaleDateString();
					}
				),
			},
			options: {
				responsive: true,
				plugins: {
					legend: { display: false },
				},
				scales: {
					y: {
						ticks: {
							min: 0,
							precision: 0,
						},
					},
				},
			},
		};

		const PieConfig = {
			type: 'pie',
			data: {
				labels: Object.keys( smtpReportData.pieData ),
				datasets: [
					{
						label: 'Total count',
						data: Object.values( smtpReportData.pieData ),
						backgroundColor: [
							'rgb(54, 162, 235)',
							'rgb(255, 99, 132)',
						],
						hoverOffset: 4,
					},
				],
				options: {
					responsive: true,
					plugins: {
						legend: { display: false },
					},
				},
			},
		};

		cf7aCharts.lineChart = new Chart(
			document.querySelector( '.smtp-style-chart > #line-chart' ),
			lineConfig
		);

		cf7aCharts.pieChart = new Chart(
			document.querySelector(
				'.smtp-style-chart > #pie-container > #pie-chart'
			),
			PieConfig
		);

		return cf7aCharts;
	}
}

window.onload = mailChartsSMTP();
