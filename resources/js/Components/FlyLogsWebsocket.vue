<template>
    <div class="fly-logs-container">
        <div class="logs-header row items-center justify-between q-mb-sm">
            <div class="text-subtitle2">
                Live Logs 
                <q-badge :color="connected ? 'positive' : 'negative'" class="q-ml-sm">
                    {{ connected ? 'Connected' : 'Disconnected' }}
                </q-badge>
            </div>
            <div>
                <q-btn
                    flat
                    dense
                    icon="mdi-delete"
                    @click="clearLogs"
                    title="Clear logs"
                />
                <q-btn
                    flat
                    dense
                    :icon="autoScroll ? 'mdi-arrow-collapse-down' : 'mdi-arrow-expand-down'"
                    @click="autoScroll = !autoScroll"
                    :title="autoScroll ? 'Disable auto-scroll' : 'Enable auto-scroll'"
                />
                <q-btn
                    flat
                    dense
                    :icon="connected ? 'mdi-pause' : 'mdi-play'"
                    @click="connected ? disconnect() : connect()"
                    :title="connected ? 'Pause logs' : 'Resume logs'"
                />
            </div>
        </div>
        
        <div ref="logsContainer" class="logs-content">
            <pre v-if="logs.length === 0" class="text-grey-6">Waiting for logs...</pre>
            <div v-else>
                <div v-for="(log, index) in logs" :key="index" class="log-entry">
                    <span class="log-timestamp">{{ formatTimestamp(log.timestamp) }}</span>
                    <span :class="'log-level-' + log.level">{{ log.level }}</span>
                    <span class="log-message">{{ log.message }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'FlyLogsWebsocket',
    props: {
        appName: {
            type: String,
            required: true
        },
        instanceId: {
            type: String,
            default: null
        },
        apiToken: {
            type: String,
            required: true
        },
        maxLogs: {
            type: Number,
            default: 1000
        }
    },
    data() {
        return {
            socket: null,
            connected: false,
            logs: [],
            autoScroll: true,
            reconnectInterval: null,
            heartbeatInterval: null,
            channelRef: null
        }
    },
    mounted() {
        this.connect();
    },
    beforeUnmount() {
        this.disconnect();
    },
    methods: {
        async connect() {
            try {
                // Get connection details from backend
                const response = await fetch(route('instances.websocket-details', {
                    app_name: this.appName,
                    instance_id: this.instanceId
                }), {
                    headers: {
                        'Authorization': `Bearer ${this.apiToken}`,
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to get websocket details');
                }

                const connectionDetails = await response.json();
                
                // Build websocket URL with params
                const params = new URLSearchParams(connectionDetails.params);
                const wsUrl = `${connectionDetails.url}?${params.toString()}`;
                
                // Connect to websocket
                this.socket = new WebSocket(wsUrl);
                
                this.socket.onopen = () => {
                    console.log('Connected to Fly.io logs websocket');
                    this.connected = true;
                    
                    // Join the logs channel
                    this.joinChannel(connectionDetails.channels);
                    
                    // Start heartbeat
                    this.startHeartbeat();
                };
                
                this.socket.onmessage = (event) => {
                    this.handleMessage(event.data);
                };
                
                this.socket.onerror = (error) => {
                    console.error('Websocket error:', error);
                    this.$q.notify({
                        type: 'negative',
                        message: 'Failed to connect to logs stream',
                        timeout: 3000
                    });
                };
                
                this.socket.onclose = () => {
                    console.log('Disconnected from Fly.io logs websocket');
                    this.connected = false;
                    this.stopHeartbeat();
                    
                    // Auto-reconnect after 5 seconds
                    if (!this.reconnectInterval) {
                        this.reconnectInterval = setTimeout(() => {
                            this.reconnectInterval = null;
                            if (!this.connected) {
                                this.connect();
                            }
                        }, 5000);
                    }
                };
            } catch (error) {
                console.error('Failed to connect to websocket:', error);
                this.$q.notify({
                    type: 'negative',
                    message: 'Failed to establish logs connection',
                    timeout: 3000
                });
            }
        },
        
        disconnect() {
            if (this.reconnectInterval) {
                clearTimeout(this.reconnectInterval);
                this.reconnectInterval = null;
            }
            
            if (this.socket) {
                this.socket.close();
                this.socket = null;
            }
            
            this.stopHeartbeat();
            this.connected = false;
        },
        
        joinChannel(channels) {
            // Phoenix channels require joining
            Object.entries(channels).forEach(([topic, config]) => {
                const joinMsg = {
                    topic: topic,
                    event: 'phx_join',
                    payload: config.params || {},
                    ref: this.generateRef(),
                    join_ref: this.generateRef()
                };
                
                this.channelRef = joinMsg.join_ref;
                this.sendMessage(joinMsg);
            });
        },
        
        handleMessage(data) {
            try {
                const message = JSON.parse(data);
                
                // Handle different Phoenix message types
                switch (message.event) {
                    case 'phx_reply':
                        // Channel join confirmation
                        if (message.payload.status === 'ok') {
                            console.log('Successfully joined channel');
                        }
                        break;
                        
                    case 'logs':
                    case 'log_entry':
                        // Handle log messages
                        this.addLog(message.payload);
                        break;
                        
                    case 'phx_error':
                        console.error('Phoenix error:', message.payload);
                        break;
                }
            } catch (error) {
                console.error('Failed to parse websocket message:', error);
            }
        },
        
        addLog(logData) {
            // Format log entry
            const logEntry = {
                timestamp: logData.timestamp || new Date().toISOString(),
                level: logData.level || 'info',
                message: logData.message || logData.data || JSON.stringify(logData),
                instance: logData.instance_id,
                region: logData.region
            };
            
            this.logs.push(logEntry);
            
            // Limit logs array size
            if (this.logs.length > this.maxLogs) {
                this.logs.splice(0, this.logs.length - this.maxLogs);
            }
            
            // Auto-scroll to bottom
            if (this.autoScroll) {
                this.$nextTick(() => {
                    this.scrollToBottom();
                });
            }
        },
        
        sendMessage(message) {
            if (this.socket && this.socket.readyState === WebSocket.OPEN) {
                this.socket.send(JSON.stringify(message));
            }
        },
        
        startHeartbeat() {
            // Phoenix requires periodic heartbeats
            this.heartbeatInterval = setInterval(() => {
                this.sendMessage({
                    topic: 'phoenix',
                    event: 'heartbeat',
                    payload: {},
                    ref: this.generateRef()
                });
            }, 30000); // Every 30 seconds
        },
        
        stopHeartbeat() {
            if (this.heartbeatInterval) {
                clearInterval(this.heartbeatInterval);
                this.heartbeatInterval = null;
            }
        },
        
        generateRef() {
            return Date.now().toString();
        },
        
        clearLogs() {
            this.logs = [];
        },
        
        scrollToBottom() {
            if (this.$refs.logsContainer) {
                this.$refs.logsContainer.scrollTop = this.$refs.logsContainer.scrollHeight;
            }
        },
        
        formatTimestamp(timestamp) {
            const date = new Date(timestamp);
            return date.toLocaleTimeString();
        }
    }
}
</script>

<style scoped>
.fly-logs-container {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.logs-content {
    flex: 1;
    overflow-y: auto;
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px;
    font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.5;
    border-radius: 4px;
}

.log-entry {
    margin-bottom: 2px;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.log-timestamp {
    color: #858585;
    margin-right: 8px;
}

.log-level-error {
    color: #f48771;
    font-weight: bold;
    margin-right: 8px;
}

.log-level-warn {
    color: #dcdcaa;
    font-weight: bold;
    margin-right: 8px;
}

.log-level-info {
    color: #569cd6;
    margin-right: 8px;
}

.log-level-debug {
    color: #858585;
    margin-right: 8px;
}

.log-message {
    color: #d4d4d4;
}

/* Dark mode adjustments */
body.body--dark .logs-content {
    background: #0d0d0d;
}
</style>