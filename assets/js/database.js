document.addEventListener('DOMContentLoaded', () => {

    const driver =
        document.getElementById('driver');

    const host =
        document.getElementById('host');

    const port =
        document.getElementById('port');

    const dbname =
        document.getElementById('dbname');

    const username =
        document.getElementById('username');

    const password =
        document.getElementById('password');

    const sqlitePath =
        document.getElementById('sqlitePath');


    const networkFields =
        document.getElementById('networkFields');

    const sqliteField =
        document.getElementById('sqliteField');


    const testButton =
        document.getElementById('testConnection');

    const loadTablesButton =
        document.getElementById('loadTables');

    const saveButton =
        document.getElementById('saveMapping');


    const tableSelect =
        document.getElementById('table');

    const caseIdSelect =
        document.getElementById('caseId');

    const activitySelect =
        document.getElementById('activity');

    const timestampSelect =
        document.getElementById('timestamp');

    const resourceSelect =
        document.getElementById('resource');


    const mappingCard =
        document.getElementById('mappingCard');

    const connectionStatus =
        document.getElementById('connectionStatus');

    const messageBox =
        document.getElementById('databaseMessage');

    const schemaPreview =
        document.getElementById('schemaPreview');


    let connectionVerified = false;


    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    */

    driver.addEventListener(
        'change',
        () => {

            connectionVerified = false;

            loadTablesButton.disabled = true;

            connectionStatus.textContent =
                'Not connected';

            connectionStatus.classList.remove(
                'connected'
            );

            if (driver.value === 'sqlite') {

                networkFields.hidden = true;
                sqliteField.hidden = false;

            } else {

                networkFields.hidden = false;
                sqliteField.hidden = true;

                port.value =
                    driver.value === 'pgsql'
                        ? '5432'
                        : '3306';
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Test
    |--------------------------------------------------------------------------
    */

    testButton.addEventListener(
        'click',
        async () => {

            clearMessage();

            testButton.disabled = true;
            testButton.textContent =
                'Testing...';

            try {

                const result = await request({
                    action: 'test_connection',
                    database: getDatabaseConfig()
                });

                connectionVerified = true;

                connectionStatus.textContent =
                    `Connected: ${result.driver}`;

                connectionStatus.classList.add(
                    'connected'
                );

                loadTablesButton.disabled = false;

                showMessage(
                    'Connection successful.',
                    'success'
                );

            } catch (error) {

                connectionVerified = false;

                loadTablesButton.disabled = true;

                connectionStatus.textContent =
                    'Connection failed';

                connectionStatus.classList.remove(
                    'connected'
                );

                showMessage(
                    error.message,
                    'error'
                );

            } finally {

                testButton.disabled = false;

                testButton.textContent =
                    'Test connection';
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    */

    loadTablesButton.addEventListener(
        'click',
        async () => {

            if (!connectionVerified) {
                return;
            }

            clearMessage();

            try {

                const result = await request({
                    action: 'get_tables',
                    database: getDatabaseConfig()
                });

                tableSelect.innerHTML =
                    '<option value="">Select table</option>';

                result.tables.forEach(table => {

                    const option =
                        document.createElement('option');

                    option.value = table;
                    option.textContent = table;

                    tableSelect.appendChild(option);
                });

                tableSelect.disabled = false;

                mappingCard.classList.remove(
                    'disabled-card'
                );

                mappingCard.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

            } catch (error) {

                showMessage(
                    error.message,
                    'error'
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Columns
    |--------------------------------------------------------------------------
    */

    tableSelect.addEventListener(
        'change',
        async () => {

            if (!tableSelect.value) {
                disableMapping();
                return;
            }

            clearMessage();

            try {

                const result = await request({
                    action: 'get_columns',
                    database: getDatabaseConfig(),
                    table: tableSelect.value
                });

                populateMappings(
                    result.columns
                );

                renderSchema(
                    result.columns
                );

                saveButton.disabled = false;

            } catch (error) {

                showMessage(
                    error.message,
                    'error'
                );
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Save & Analyze
    |--------------------------------------------------------------------------
    */

    saveButton.addEventListener(
        'click',
        async () => {

            clearMessage();

            saveButton.disabled = true;

            saveButton.textContent =
                'Preparing analysis...';

            try {

                await request({
                    action: 'save',

                    database:
                        getDatabaseConfig(),

                    mapping: {
                        table:
                            tableSelect.value,

                        case_id:
                            caseIdSelect.value,

                        event:
                            activitySelect.value,

                        timestamp:
                            timestampSelect.value,

                        resource:
                            resourceSelect.value
                    }
                });

                window.location.href =
                    'index.php';

            } catch (error) {

                showMessage(
                    error.message,
                    'error'
                );

                saveButton.disabled = false;

                saveButton.textContent =
                    'Save & Analyze';
            }
        }
    );


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    function getDatabaseConfig() {

        if (driver.value === 'sqlite') {

            return {
                driver: 'sqlite',
                path: sqlitePath.value.trim()
            };
        }

        return {
            driver: driver.value,

            host:
                host.value.trim(),

            port:
                port.value.trim(),

            dbname:
                dbname.value.trim(),

            username:
                username.value,

            password:
                password.value
        };
    }


    function populateMappings(columns) {

        const names =
            columns.map(column => column.name);

        populateSelect(
            caseIdSelect,
            names
        );

        populateSelect(
            activitySelect,
            names
        );

        populateSelect(
            timestampSelect,
            names
        );

        populateSelect(
            resourceSelect,
            names,
            true
        );


        /*
         * Lightweight automatic suggestions.
         */

        selectSuggestion(
            caseIdSelect,
            [
                'case_id',
                'order_id',
                'process_id',
                'ticket_id',
                'id'
            ]
        );

        selectSuggestion(
            activitySelect,
            [
                'activity',
                'event',
                'event_type',
                'status',
                'action'
            ]
        );

        selectSuggestion(
            timestampSelect,
            [
                'timestamp',
                'created_at',
                'event_time',
                'time',
                'date'
            ]
        );

        selectSuggestion(
            resourceSelect,
            [
                'resource',
                'employee_id',
                'user_id',
                'operator',
                'assignee'
            ]
        );
    }


    function populateSelect(
        select,
        values,
        optional = false
    ) {

        select.innerHTML = '';

        if (optional) {

            const none =
                document.createElement('option');

            none.value = '';
            none.textContent =
                'None';

            select.appendChild(none);
        }

        values.forEach(value => {

            const option =
                document.createElement('option');

            option.value = value;
            option.textContent = value;

            select.appendChild(option);
        });

        select.disabled = false;
    }


    function selectSuggestion(
        select,
        candidates
    ) {

        for (const candidate of candidates) {

            const option =
                Array.from(select.options)
                    .find(
                        item =>
                            item.value
                                .toLowerCase()
                            === candidate
                    );

            if (option) {

                select.value =
                    option.value;

                return;
            }
        }
    }


    function renderSchema(columns) {

        schemaPreview.innerHTML = `
            <div class="schema-title">
                Detected schema
            </div>

            ${columns.map(column => `
                <div class="schema-column">

                    <strong>
                        ${escapeHtml(column.name)}
                    </strong>

                    <span>
                        ${escapeHtml(column.type)}
                    </span>

                    ${
                        column.primary_key
                            ? '<em>PK</em>'
                            : ''
                    }

                </div>
            `).join('')}
        `;
    }


    function disableMapping() {

        [
            caseIdSelect,
            activitySelect,
            timestampSelect,
            resourceSelect
        ].forEach(select => {

            select.disabled = true;
            select.innerHTML = '';
        });

        saveButton.disabled = true;

        schemaPreview.innerHTML = '';
    }


    async function request(payload) {

        const response = await fetch(
            'database-api.php',
            {
                method: 'POST',

                headers: {
                    'Content-Type':
                        'application/json'
                },

                body: JSON.stringify(
                    payload
                )
            }
        );

        const result =
            await response.json();

        if (
            !response.ok
            || result.success !== true
        ) {
            throw new Error(
                result.message
                || 'SpectoDB request failed.'
            );
        }

        return result;
    }


    function showMessage(
        message,
        type
    ) {

        messageBox.hidden = false;

        messageBox.className =
            `database-message ${type}`;

        messageBox.textContent =
            message;
    }


    function clearMessage() {

        messageBox.hidden = true;

        messageBox.className =
            'database-message';

        messageBox.textContent = '';
    }


    function escapeHtml(value) {

        const element =
            document.createElement('div');

        element.textContent =
            String(value ?? '');

        return element.innerHTML;
    }

});