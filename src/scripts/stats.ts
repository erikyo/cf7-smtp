/* global smtpReportData */
import Chart from 'chart.js/auto';

interface SmtpReportData {
	storage: Record< string, { mail_sent: boolean } >;
	lineData?: {
		success: Record< string, number >;
		failed: Record< string, number >;
	};
	pieData?: {
		success: number;
		failed: number;
	};
}

interface Charts {
	lineChart: Chart;
	pieChart: Chart;
}

declare global {
	interface Window {
		smtpReportData?: SmtpReportData;
	}
}

/**
 * Initialize the charts for the SMTP report page.
 *
 * @return {Charts | undefined} The charts object or undefined if the data is not available.
 */
export function mailChartsSMTP(): Charts | undefined {
	if (
		typeof window.smtpReportData !== 'undefined' &&
		0 !== Object.keys( window.smtpReportData ).length
	) {
		const cf7aCharts: Partial< Charts > = {};
		window.smtpReportData.lineData = {
			success: {},
			failed: {},
		};
		window.smtpReportData.pieData = {
			success: 0,
			failed: 0,
		};

		for ( const timestamp in window.smtpReportData.storage ) {
			const day = new Date( Number( timestamp ) * 1000 )
				.setHours( 0, 0, 0, 0 )
				.valueOf();

			if (
				typeof window.smtpReportData.lineData.failed[ day ] ===
				'undefined'
			) {
				window.smtpReportData.lineData.failed[ day ] = 0;
			}
			if (
				typeof window.smtpReportData.lineData.success[ day ] ===
				'undefined'
			) {
				window.smtpReportData.lineData.success[ day ] = 0;
			}

			if (
				window.smtpReportData.storage[ timestamp ].mail_sent === true
			) {
				window.smtpReportData.lineData.success[ day ]++;
				window.smtpReportData.pieData.success++;
			} else {
				window.smtpReportData.lineData.failed[ day ]++;
				window.smtpReportData.pieData.failed++;
			}
		}

		const lineConfig = {
			type: 'line' as const,
			data: {
				datasets: [
					{
						label: 'Failed',
						data: Object.values(
							window.smtpReportData.lineData.failed
						),
						fill: false,
						borderColor: 'rgb(255, 99, 132)',
						tension: 0.1,
					},
					{
						label: 'Success',
						data: Object.values(
							window.smtpReportData.lineData.success
						),
						fill: false,
						borderColor: 'rgb(54, 162, 235)',
						tension: 0.1,
					},
				],
				labels: Object.keys(
					window.smtpReportData.lineData.failed
				).map( ( label ) => {
					const step = new Date( parseInt( label, 10 ) );
					return step.toLocaleDateString();
				} ),
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

		const pieConfig = {
			type: 'pie' as const,
			data: {
				labels: Object.keys( window.smtpReportData.pieData ),
				datasets: [
					{
						label: 'Total count',
						data: Object.values( window.smtpReportData.pieData ),
						backgroundColor: [
							'rgb(54, 162, 235)',
							'rgb(255, 99, 132)',
						],
						hoverOffset: 4,
					},
				],
			},
			options: {
				responsive: true,
				plugins: {
					legend: { display: false },
				},
			},
		};

		const lineChartElement = document.querySelector(
			'.smtp-style-chart > #smtp-line-chart'
		) as HTMLCanvasElement;
		const pieChartElement = document.querySelector(
			'.smtp-style-chart #smtp-pie-chart'
		) as HTMLCanvasElement;

		if ( lineChartElement ) {
			cf7aCharts.lineChart = new Chart( lineChartElement, lineConfig );
		}

		if ( pieChartElement ) {
			cf7aCharts.pieChart = new Chart( pieChartElement, pieConfig );
		}

		return cf7aCharts as Charts;
	}
}

window.onload = mailChartsSMTP;
