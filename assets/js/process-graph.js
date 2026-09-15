document.addEventListener('DOMContentLoaded', () => {

    const graphData = window.processGraphData;

    if (!graphData) {
        return;
    }

    const nodes = (graphData.nodes || []).map(node => ({
        data: {
            id: node.id,
            label: node.label,
            intensity: node.intensity || 0,
            start: node.start || false,
            end: node.end || false
        }
    }));

    const edges = (graphData.edges || []).map((edge, index) => ({
        data: {
            id: `edge-${index}`,
            source: edge.from,
            target: edge.to,

            transitionId: edge.id,

            count: edge.count || 0,
            intensity: edge.intensity || 0,

            baseline: edge.baseline || false,
            deviation: edge.deviation || false,
            bottleneck: edge.bottleneck || false
        }
    }));

    const cy = cytoscape({

        container: document.getElementById('processGraph'),

        elements: [
            ...nodes,
            ...edges
        ],

        layout: {
            name: 'breadthfirst',
            directed: true,
            padding: 50,
            spacingFactor: 1.4
        },

        style: [

            /* Nodes */

            {
                selector: 'node',

                style: {
                    'background-color': '#242424',

                    'border-width': 2,
                    'border-color': '#464646',

                    'label': 'data(label)',

                    'color': '#f0f0f0',

                    'font-size': 12,
                    'font-weight': 600,

                    'text-valign': 'center',
                    'text-halign': 'center',

                    'width': 125,
                    'height': 55,

                    'shape': 'round-rectangle',

                    'text-wrap': 'wrap',
                    'text-max-width': 100,

                    'overlay-opacity': 0
                }
            },

            {
                selector: 'node[start = true]',

                style: {
                    'border-color': '#4caf7d',
                    'border-width': 3
                }
            },

            {
                selector: 'node[end = true]',

                style: {
                    'border-color': '#5c8cff',
                    'border-width': 3
                }
            },

            /* Edges */

            {
                selector: 'edge',

                style: {
                    'width': 2,

                    'line-color': '#555',

                    'target-arrow-color': '#555',
                    'target-arrow-shape': 'triangle',

                    'curve-style': 'bezier',

                    'label': 'data(count)',

                    'font-size': 10,
                    'color': '#aaa',

                    'text-background-color': '#151515',
                    'text-background-opacity': 1,
                    'text-background-padding': 3,

                    'arrow-scale': 0.8,

                    'overlay-opacity': 0
                }
            },

            /* Baseline */

            {
                selector: 'edge[baseline = true]',

                style: {
                    'line-color': '#747474',
                    'target-arrow-color': '#747474'
                }
            },

            /* Deviations */

            {
                selector: 'edge[deviation = true]',

                style: {
                    'line-color': '#d6a343',
                    'target-arrow-color': '#d6a343',

                    'line-style': 'dashed',

                    'width': 3
                }
            },

            /* Bottlenecks */

            {
                selector: 'edge[bottleneck = true]',

                style: {
                    'line-color': '#dc5b5b',
                    'target-arrow-color': '#dc5b5b',

                    'width': 6,

                    'line-style': 'solid'
                }
            },

            /* Selected */

            {
                selector: ':selected',

                style: {
                    'border-color': '#ffffff',
                    'border-width': 3
                }
            }

        ]
    });

    /* Details panel */

    const detailsTitle =
        document.getElementById('detailsTitle');

    const detailsContent =
        document.getElementById('detailsContent');


    /* Node click */

    cy.on('tap', 'node', event => {

        const node = event.target.data();

        detailsTitle.textContent =
            node.label;

        let type = 'Activity';

        if (node.start && node.end) {
            type = 'Start / End activity';
        } else if (node.start) {
            type = 'Start activity';
        } else if (node.end) {
            type = 'End activity';
        }

        detailsContent.innerHTML = `
            <div class="detail-row">
                <span>Type</span>
                <strong>${escapeHtml(type)}</strong>
            </div>

            <div class="detail-row">
                <span>Activity</span>
                <strong>${escapeHtml(node.label)}</strong>
            </div>

            <div class="detail-row">
                <span>Frequency intensity</span>
                <strong>${formatIntensity(node.intensity)}</strong>
            </div>
        `;
    });


    /* Edge click */

    cy.on('tap', 'edge', event => {

        const edge = event.target.data();

        detailsTitle.textContent =
            `${edge.source} → ${edge.target}`;

        let status = 'Normal transition';

        if (edge.bottleneck) {
            status = 'Potential bottleneck';
        } else if (edge.deviation) {
            status = 'Process deviation';
        }

        detailsContent.innerHTML = `
            <div class="detail-row">
                <span>Status</span>
                <strong>${escapeHtml(status)}</strong>
            </div>

            <div class="detail-row">
                <span>From</span>
                <strong>${escapeHtml(edge.source)}</strong>
            </div>

            <div class="detail-row">
                <span>To</span>
                <strong>${escapeHtml(edge.target)}</strong>
            </div>

            <div class="detail-row">
                <span>Occurrences</span>
                <strong>${edge.count}</strong>
            </div>

            <div class="detail-row">
                <span>Frequency intensity</span>
                <strong>${formatIntensity(edge.intensity)}</strong>
            </div>

            <div class="detail-row">
                <span>Main process</span>
                <strong>${edge.baseline ? 'Yes' : 'No'}</strong>
            </div>
        `;
    });


    /* Controls */

    document
        .getElementById('fitGraph')
        ?.addEventListener('click', () => {

            cy.fit(
                cy.elements(),
                50
            );

        });


    document
        .getElementById('resetGraph')
        ?.addEventListener('click', () => {

            cy.layout({
                name: 'breadthfirst',
                directed: true,
                padding: 50,
                spacingFactor: 1.4
            }).run();

        });


    /*Helpers*/

    function formatIntensity(value) {

        const numeric =
            Number(value || 0);

        return `${Math.round(numeric * 100)}%`;
    }


    function escapeHtml(value) {

        const div =
            document.createElement('div');

        div.textContent =
            String(value ?? '');

        return div.innerHTML;
    }

});