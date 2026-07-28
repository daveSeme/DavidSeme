import { Redis } from '@upstash/redis';

export const config = { runtime: 'edge' };

const redis = new Redis({
  url: process.env.UPSTASH_REDIS_REST_URL,
  token: process.env.UPSTASH_REDIS_REST_TOKEN,
});

export default async function handler(req) {
  if (req.method !== 'POST') {
    return new Response(JSON.stringify({ error: 'Method not allowed' }), { 
      status: 405,
      headers: { 'Content-Type': 'application/json' }
    });
  }

  try {
    const data = await req.json();
    const requestId = `request:${Date.now()}:${Math.random().toString(36).substr(2, 9)}`;
    
    await redis.hset(requestId, {
      name: data.name,
      email: data.email,
      project: data.project,
      interest: data.interest,
      message: data.message || '',
      status: 'pending',
      requestedAt: new Date().toISOString()
    });
    
    await redis.zadd('requests:index', { score: Date.now(), member: requestId });

    return new Response(JSON.stringify({ status: 'ok' }), {
      status: 200,
      headers: { 'Content-Type': 'application/json' }
    });
  } catch (error) {
    return new Response(JSON.stringify({ error: error.message }), {
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}