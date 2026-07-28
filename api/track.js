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
    const visitorId = `visitor:${Date.now()}:${Math.random().toString(36).substr(2, 9)}`;
    
    await redis.hset(visitorId, {
      ip: req.headers.get('x-forwarded-for') || 'unknown',
      page: data.page || '/',
      referrer: data.referrer || 'Direct',
      visitedAt: new Date().toISOString()
    });
    
    await redis.zadd('visitors:index', { score: Date.now(), member: visitorId });
    
    const today = new Date().toISOString().split('T')[0];
    await redis.hincrby('daily:count', today, 1);
    await redis.hincrby('pages:count', data.page || '/', 1);

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