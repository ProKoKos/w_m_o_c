import React, { useState, useEffect, useCallback } from 'react';
import axios from 'axios'; // Assuming axios is available or can be installed

interface MinerMessage {
    timestamp: string;
    type: string;
    payload: any;
}

interface CommandToSend {
    command_type: string;
    target_api_command_cmd: string;
    target_api_command_param_json: string; // JSON string from textarea
}

export default function MinerDashboardPage() {
    const [minerId, setMinerId] = useState<string>('default_miner_123');
    const [messages, setMessages] = useState<MinerMessage[]>([]);
    const [command, setCommand] = useState<CommandToSend>({
        command_type: 'execute_whatsminer_api',
        target_api_command_cmd: 'get.miner.status',
        target_api_command_param_json: '{}',
    });
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [error, setError] = useState<string | null>(null);

    const fetchMessages = useCallback(async () => {
        if (!minerId) return;
        setIsLoading(true);
        try {
            const response = await axios.get(`/miner_log?miner_id=${minerId}`);
            setMessages(response.data.sort((a: MinerMessage, b: MinerMessage) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime()));
            setError(null);
        } catch (err) {
            console.error("Error fetching messages:", err);
            setError('Failed to fetch messages.');
            setMessages([]); // Clear messages on error
        }
        setIsLoading(false);
    }, [minerId]);

    useEffect(() => {
        fetchMessages();
        const intervalId = setInterval(fetchMessages, 5000); // Refresh every 5 seconds
        return () => clearInterval(intervalId);
    }, [fetchMessages]);

    const handleSendCommand = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!minerId) {
            setError("Miner ID is required to send a command.");
            return;
        }
        setIsLoading(true);
        try {
            let paramsJson;
            try {
                paramsJson = JSON.parse(command.target_api_command_param_json || '{}');
            } catch (jsonError) {
                setError("Invalid JSON in command parameters.");
                setIsLoading(false);
                return;
            }

            await axios.post('/send_miner_command', {
                miner_id: minerId,
                command_type: command.command_type,
                target_api_command_cmd: command.target_api_command_cmd,
                target_api_command_param_json: paramsJson,
            });
            setError(null);
            // Optionally clear command form or give success message
            // Messages will refresh automatically
        } catch (err) {
            console.error("Error sending command:", err);
            setError('Failed to send command.');
        }
        setIsLoading(false);
        fetchMessages(); // Refresh messages immediately after sending command
    };

    const handleClearLog = async () => {
        if (!minerId) {
            setError("Miner ID is required to clear logs.");
            return;
        }
        if (!confirm(`Are you sure you want to clear all data for miner '${minerId}'?`)) {
            return;
        }
        setIsLoading(true);
        try {
            await axios.post('/clear_miner_log', { miner_id: minerId });
            setMessages([]); // Clear messages locally
            setError(null);
        } catch (err) {
            console.error("Error clearing log:", err);
            setError('Failed to clear log.');
        }
        setIsLoading(false);
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setCommand(prev => ({ ...prev, [name]: value }));
    };
    
    const commonInputStyle = "mt-1 block w-full px-3 py-2 bg-white border border-slate-300 rounded-md text-sm shadow-sm placeholder-slate-400 focus:outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500 disabled:bg-slate-50 disabled:text-slate-500 disabled:border-slate-200 disabled:shadow-none invalid:border-pink-500 invalid:text-pink-600 focus:invalid:border-pink-500 focus:invalid:ring-pink-500";
    const commonLabelStyle = "block text-sm font-medium text-slate-700";
    const commonButtonStyle = "px-4 py-2 font-semibold text-sm bg-sky-500 text-white rounded-md shadow-sm hover:bg-sky-600 disabled:opacity-50";


    return (
        <div className="p-4 max-w-4xl mx-auto">
            <h1 className="text-2xl font-bold mb-4">Miner Interaction Dashboard</h1>

            <div className="mb-6 p-4 border rounded-lg shadow-sm bg-white">
                <label htmlFor="minerId" className={commonLabelStyle}>Miner ID (wmoc_assigned_id):</label>
                <input
                    type="text"
                    id="minerId"
                    name="minerId"
                    value={minerId}
                    onChange={(e) => setMinerId(e.target.value)}
                    className={commonInputStyle}
                    placeholder="Enter Miner ID (e.g., default_miner_123)"
                />
            </div>

            {error && <div className="mb-4 p-3 bg-red-100 text-red-700 border border-red-400 rounded">Error: {error}</div>}

            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div className="p-4 border rounded-lg shadow-sm bg-white">
                    <h2 className="text-xl font-semibold mb-3">Send Command to Miner</h2>
                    <form onSubmit={handleSendCommand}>
                        <div className="mb-3">
                            <label htmlFor="command_type" className={commonLabelStyle}>Command Type:</label>
                            <select name="command_type" id="command_type" value={command.command_type} onChange={handleInputChange} className={commonInputStyle}>
                                <option value="execute_whatsminer_api">Execute Whatsminer API</option>
                                <option value="update_agent_config">Update Agent Config</option>
                                <option value="request_agent_logs">Request Agent Logs</option>
                            </select>
                        </div>
                        <div className="mb-3">
                            <label htmlFor="target_api_command_cmd" className={commonLabelStyle}>Target API Command (cmd):</label>
                            <input
                                type="text"
                                id="target_api_command_cmd"
                                name="target_api_command_cmd"
                                value={command.target_api_command_cmd}
                                onChange={handleInputChange}
                                className={commonInputStyle}
                                placeholder="e.g., get.miner.status"
                            />
                        </div>
                        <div className="mb-3">
                            <label htmlFor="target_api_command_param_json" className={commonLabelStyle}>Target API Command Parameters (JSON):</label>
                            <textarea
                                id="target_api_command_param_json"
                                name="target_api_command_param_json"
                                value={command.target_api_command_param_json}
                                onChange={handleInputChange}
                                className={`${commonInputStyle} min-h-[80px]`}
                                placeholder='e.g., {"param_name": "value"}'
                            />
                        </div>
                        <button type="submit" disabled={isLoading || !minerId} className={commonButtonStyle}>
                            {isLoading ? 'Sending...' : 'Send Command'}
                        </button>
                    </form>
                </div>

                <div className="p-4 border rounded-lg shadow-sm bg-white">
                    <h2 className="text-xl font-semibold mb-2">Message Log</h2>
                     <button onClick={handleClearLog} disabled={isLoading || !minerId} className={`${commonButtonStyle} bg-red-500 hover:bg-red-600 mb-3`}>
                        {isLoading ? 'Clearing...' : 'Clear Log for this Miner'}
                    </button>
                    <div className="h-96 overflow-y-auto border rounded p-2 bg-slate-50">
                        {messages.length === 0 && !isLoading && <p className="text-slate-500">No messages yet for miner '{minerId}'.</p>}
                        {isLoading && messages.length === 0 && <p className="text-slate-500">Loading messages...</p>}
                        {messages.map((msg, index) => (
                            <div key={index} className={`p-2 mb-2 border rounded shadow-sm text-xs ${msg.type.startsWith('sent') ? 'bg-blue-50' : 'bg-green-50'}`}>
                                <p className="font-semibold"><strong>[{msg.type.replace(/_/g, ' ').toUpperCase()}]</strong> @ {new Date(msg.timestamp).toLocaleString()}</p>
                                <pre className="whitespace-pre-wrap break-all">{JSON.stringify(msg.payload, null, 2)}</pre>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

